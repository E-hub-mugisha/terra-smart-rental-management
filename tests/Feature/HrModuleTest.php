<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Property;
use App\Models\PropertyExpense;
use App\Models\PropertyOwner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** HR: employees, attendance, leave and payroll, scoped per employer. */
class HrModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $ownerA;
    private User $ownerB;
    private Property $propertyA;
    private Property $propertyB;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-18 09:00:00'); // a Wednesday

        $this->admin = User::factory()->create(['role' => 'admin']);
        [$this->ownerA, $this->propertyA] = $this->ownerWithProperty('A');
        [$this->ownerB, $this->propertyB] = $this->ownerWithProperty('B');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function ownerWithProperty(string $tag): array
    {
        $user = User::factory()->create(['role' => 'owner']);
        $owner = PropertyOwner::forceCreate(['user_id' => $user->id, 'owner_number' => 'O' . $tag, 'name' => 'Owner ' . $tag, 'status' => 'active', 'created_by' => $this->admin->id]);
        $property = Property::forceCreate(['property_owner_id' => $owner->id, 'property_code' => 'P' . $tag, 'name' => 'Property ' . $tag, 'property_type' => 'residential', 'status' => 'active', 'created_by' => $this->admin->id]);

        return [$user, $property];
    }

    private function employee(?Property $property, array $attributes = []): Employee
    {
        return Employee::create($attributes + [
            'employee_number' => Employee::nextNumber(), 'property_id' => $property?->id, 'full_name' => 'Staff ' . uniqid(),
            'job_title' => 'Caretaker', 'hire_date' => '2026-01-01', 'base_salary' => 200000, 'currency' => 'RWF',
            'employment_type' => 'permanent', 'annual_leave_days' => 18, 'status' => 'active',
        ]);
    }

    public function test_owners_only_see_and_manage_their_own_staff_and_admins_manage_terra_staff(): void
    {
        $this->actingAs($this->ownerA)->post(route('hr.employees.store'), [
            'property_id' => $this->propertyA->id, 'full_name' => 'Jean Caretaker', 'job_title' => 'Caretaker', 'phone' => '0788123456',
            'hire_date' => '2026-03-01', 'employment_type' => 'permanent', 'base_salary' => 150000, 'currency' => 'RWF',
            'annual_leave_days' => 18, 'status' => 'active',
        ])->assertRedirect();
        $mine = Employee::where('full_name', 'Jean Caretaker')->firstOrFail();
        $this->assertSame('+250788123456', $mine->phone);
        $this->assertSame($this->ownerA->id, $mine->created_by);

        $theirs = $this->employee($this->propertyB, ['full_name' => 'Other Guard']);
        $internal = $this->employee(null, ['full_name' => 'Terra Accountant']);

        $this->actingAs($this->ownerA)->get(route('hr.employees.index'))->assertOk()
            ->assertSee('Jean Caretaker')->assertDontSee('Other Guard')->assertDontSee('Terra Accountant');
        $this->actingAs($this->ownerA)->get(route('hr.employees.show', $theirs))->assertForbidden();
        $this->actingAs($this->ownerA)->get(route('hr.employees.show', $internal))->assertForbidden();
        $this->actingAs($this->ownerA)->get(route('hr.employees.index', ['employer' => $this->propertyB->id]))->assertForbidden();

        // An owner cannot hire for someone else's property, nor as Terra staff.
        $payload = ['full_name' => 'Sneaky', 'job_title' => 'Guard', 'hire_date' => '2026-03-01', 'employment_type' => 'casual',
            'base_salary' => 1, 'currency' => 'RWF', 'annual_leave_days' => 18, 'status' => 'active'];
        $this->actingAs($this->ownerA)->post(route('hr.employees.store'), $payload + ['property_id' => $this->propertyB->id])->assertForbidden();
        $this->actingAs($this->ownerA)->post(route('hr.employees.store'), $payload)->assertSessionHasErrors('property_id');
        $this->assertSame(0, Employee::where('full_name', 'Sneaky')->count());

        // Admin sees everyone, can hire Terra staff and filter to them.
        $this->actingAs($this->admin)->post(route('hr.employees.store'), ['full_name' => 'Terra Officer'] + $payload)->assertRedirect();
        $officer = Employee::where('full_name', 'Terra Officer')->firstOrFail();
        $this->assertNull($officer->property_id);
        $this->actingAs($this->admin)->get(route('hr.employees.index', ['employer' => 'internal']))->assertOk()
            ->assertSee('Terra Officer')->assertSee('Terra Accountant')->assertDontSee('Jean Caretaker');

        // Tenants and other roles have no HR.
        $tenant = User::factory()->create(['role' => 'tenant']);
        $this->actingAs($tenant)->get(route('hr.dashboard'))->assertForbidden();
    }

    public function test_attendance_register_saves_updates_and_prefills_leave(): void
    {
        $a = $this->employee($this->propertyA, ['full_name' => 'Alice Cleaner']);
        $b = $this->employee($this->propertyA, ['full_name' => 'Bob Guard']);
        $other = $this->employee($this->propertyB);
        LeaveRequest::create(['employee_id' => $b->id, 'type' => 'sick', 'start_date' => '2026-11-17', 'end_date' => '2026-11-19', 'days' => 3, 'status' => 'approved']);

        $this->actingAs($this->ownerA)->get(route('hr.attendance.index'))->assertOk()
            ->assertSee('Alice Cleaner')->assertSee('Sick leave')->assertDontSee($other->full_name);

        $save = fn (array $rows) => $this->actingAs($this->ownerA)->post(route('hr.attendance.store'), ['date' => '2026-11-18', 'rows' => $rows]);
        $save([$a->id => ['status' => 'present', 'check_in' => '07:55', 'check_out' => '17:00'], $b->id => ['status' => 'leave']])->assertRedirect();
        $save([$a->id => ['status' => 'late', 'check_in' => '09:10']])->assertRedirect(); // same day again: updated, not duplicated

        $this->assertSame(2, Attendance::count());
        $this->assertSame('late', Attendance::where('employee_id', $a->id)->value('status'));

        // Someone else's staff cannot be marked, and the future cannot be marked.
        $save([$other->id => ['status' => 'absent']])->assertForbidden();
        $this->actingAs($this->ownerA)->post(route('hr.attendance.store'), ['date' => '2026-11-19', 'rows' => [$a->id => ['status' => 'present']]])
            ->assertSessionHasErrors('date');
    }

    public function test_leave_counts_working_days_respects_the_balance_and_blocks_overlaps(): void
    {
        $e = $this->employee($this->propertyA, ['annual_leave_days' => 5]);

        // Fri 20 – Tue 24 Nov = 3 working days.
        $this->actingAs($this->ownerA)->post(route('hr.leave.store'), ['employee_id' => $e->id, 'type' => 'annual', 'start_date' => '2026-11-20', 'end_date' => '2026-11-24'])
            ->assertSessionHasNoErrors();
        $leave = LeaveRequest::firstOrFail();
        $this->assertEquals(3, (float) $leave->days);
        $this->assertSame('pending', $leave->status);

        // Overlapping request refused.
        $this->actingAs($this->ownerA)->post(route('hr.leave.store'), ['employee_id' => $e->id, 'type' => 'sick', 'start_date' => '2026-11-23', 'end_date' => '2026-11-23'])
            ->assertSessionHasErrors('start_date');

        $this->actingAs($this->ownerA)->post(route('hr.leave.decide', $leave), ['decision' => 'approve'])->assertSessionHas('success');
        $this->assertEquals(2, $e->fresh()->annualLeaveBalance(2026));

        // 3 more annual days would exceed the remaining 2.
        $this->actingAs($this->ownerA)->post(route('hr.leave.store'), ['employee_id' => $e->id, 'type' => 'annual', 'start_date' => '2026-12-01', 'end_date' => '2026-12-03', 'approve_now' => 1])
            ->assertSessionHasErrors('leave');

        // Cancelling gives the days back; others cannot touch it.
        $this->actingAs($this->ownerB)->post(route('hr.leave.decide', $leave), ['decision' => 'cancel'])->assertForbidden();
        $this->actingAs($this->ownerA)->post(route('hr.leave.decide', $leave), ['decision' => 'cancel']);
        $this->assertEquals(5, $e->fresh()->annualLeaveBalance(2026));

        $this->actingAs($this->ownerA)->get(route('hr.leave.index', ['status' => 'all']))->assertOk()->assertSee($e->full_name);
    }

    public function test_payroll_from_draft_to_paid_books_a_salaries_expense(): void
    {
        $a = $this->employee($this->propertyA, ['full_name' => 'Alice Cleaner', 'base_salary' => 200000]);
        $b = $this->employee($this->propertyA, ['full_name' => 'Bob Guard', 'base_salary' => 150000]);
        $this->employee($this->propertyA, ['full_name' => 'Starts Later', 'hire_date' => '2026-12-05']);
        $this->employee($this->propertyA, ['full_name' => 'Paid In Dollars', 'currency' => 'USD']);
        $this->employee($this->propertyB, ['full_name' => 'Other Property']);
        Attendance::create(['employee_id' => $b->id, 'date' => '2026-11-03', 'status' => 'absent']);

        $this->actingAs($this->ownerA)->post(route('hr.payroll.store'), ['property_id' => $this->propertyA->id, 'month' => '2026-11', 'currency' => 'RWF'])
            ->assertRedirect();
        $run = PayrollRun::firstOrFail();
        $this->assertSame(['Alice Cleaner', 'Bob Guard'], $run->payslips()->with('employee')->get()->pluck('employee.full_name')->sort()->values()->all());
        $this->assertEquals(350000, (float) $run->total_net);
        $this->assertSame(1, Payslip::where('employee_id', $b->id)->value('days_absent'));

        // Same employer, month and currency twice is refused.
        $this->actingAs($this->ownerA)->post(route('hr.payroll.store'), ['property_id' => $this->propertyA->id, 'month' => '2026-11', 'currency' => 'RWF'])
            ->assertSessionHasErrors('payroll');

        // Edit: allowances and deductions recalculate the net.
        $slipA = Payslip::where('employee_id', $a->id)->firstOrFail();
        $this->actingAs($this->ownerA)->put(route('hr.payroll.update', $run), ['lines' => [
            $slipA->id => ['base_salary' => 200000, 'allowances' => 20000, 'overtime' => 0, 'paye' => 30000, 'pension' => 12000, 'other_deductions' => 0],
        ]])->assertSessionHas('success');
        $slipA->refresh();
        $this->assertEquals(220000, (float) $slipA->gross_pay);
        $this->assertEquals(178000, (float) $slipA->net_pay);
        $this->assertEquals(328000, (float) $run->fresh()->total_net);

        // Deductions above gross are refused.
        $this->actingAs($this->ownerA)->put(route('hr.payroll.update', $run), ['lines' => [$slipA->id => ['base_salary' => 100, 'allowances' => 0, 'overtime' => 0, 'paye' => 500]]])
            ->assertSessionHasErrors('payroll');

        // Other owners cannot see or change it.
        $this->actingAs($this->ownerB)->get(route('hr.payroll.show', $run))->assertForbidden();
        $this->actingAs($this->ownerB)->get(route('hr.payslips.show', $slipA))->assertForbidden();

        // Cannot pay before approval; approval locks editing.
        $this->actingAs($this->ownerA)->post(route('hr.payroll.pay', $run), ['payment_method' => 'mobile_money', 'paid_on' => '2026-11-18'])->assertSessionHasErrors('payroll');
        $this->actingAs($this->ownerA)->post(route('hr.payroll.approve', $run))->assertSessionHas('success');
        $this->actingAs($this->ownerA)->put(route('hr.payroll.update', $run), ['lines' => [$slipA->id => ['allowances' => 1]]])->assertSessionHasErrors('payroll');

        $this->actingAs($this->ownerA)->post(route('hr.payroll.pay', $run), ['payment_method' => 'mobile_money', 'paid_on' => '2026-11-18'])->assertSessionHas('success');
        $run->refresh();
        $this->assertSame('paid', $run->status);
        $expense = PropertyExpense::findOrFail($run->property_expense_id);
        $this->assertSame($this->propertyA->id, $expense->property_id);
        $this->assertSame('Salaries', $expense->category);
        $this->assertEquals(328000, (float) $expense->amount);

        // Paid payroll stays in the books.
        $this->actingAs($this->ownerA)->delete(route('hr.payroll.destroy', $run))->assertSessionHasErrors('payroll');
        $this->assertModelExists($run);

        // Pages render.
        $this->actingAs($this->ownerA)->get(route('hr.payroll.show', $run))->assertOk()->assertSee('Alice Cleaner');
        $this->actingAs($this->ownerA)->get(route('hr.payslips.show', $slipA))->assertOk()->assertSee('178,000');
        $this->actingAs($this->ownerA)->get(route('hr.payroll.index'))->assertOk()->assertSee('November 2026');

        // An employee who has been paid is kept (soft-deleted) so the payslip survives.
        $this->actingAs($this->ownerA)->delete(route('hr.employees.destroy', $a))->assertRedirect();
        $this->assertSoftDeleted($a);
        $this->assertModelExists($slipA);
    }

    public function test_admin_runs_terra_payroll_and_pages_render_for_everyone(): void
    {
        $internal = $this->employee(null, ['full_name' => 'Terra Officer', 'base_salary' => 500000]);
        $this->employee($this->propertyA, ['full_name' => 'Alice Cleaner']);

        $this->actingAs($this->admin)->post(route('hr.payroll.store'), ['month' => '2026-11', 'currency' => 'RWF'])->assertRedirect();
        $run = PayrollRun::whereNull('property_id')->firstOrFail();
        $this->assertSame([$internal->id], $run->payslips()->pluck('employee_id')->all());

        $this->actingAs($this->admin)->post(route('hr.payroll.approve', $run));
        $this->actingAs($this->admin)->post(route('hr.payroll.pay', $run), ['payment_method' => 'bank', 'paid_on' => '2026-11-18']);
        $this->assertNull($run->fresh()->property_expense_id); // Terra's own payroll is not a property expense

        // Owners cannot run payroll for Terra.
        $this->actingAs($this->ownerA)->post(route('hr.payroll.store'), ['month' => '2026-10', 'currency' => 'RWF'])->assertSessionHasErrors('property_id');

        foreach ([$this->admin, $this->ownerA] as $user) {
            foreach (['hr.dashboard', 'hr.employees.index', 'hr.employees.create', 'hr.attendance.index', 'hr.leave.index', 'hr.payroll.index'] as $route) {
                $this->actingAs($user)->get(route($route))->assertOk();
            }
        }
        $this->actingAs($this->admin)->get(route('hr.employees.show', $internal))->assertOk()->assertSee('Terra (internal staff)')->assertSee('Delete permanently');
        $this->actingAs($this->ownerA)->get(route('hr.dashboard'))->assertOk()->assertSee('Payroll');
    }

    public function test_purging_a_property_removes_its_hr_data(): void
    {
        $e = $this->employee($this->propertyA);
        $kept = $this->employee($this->propertyB);
        Attendance::create(['employee_id' => $e->id, 'date' => '2026-11-18', 'status' => 'present']);
        LeaveRequest::create(['employee_id' => $e->id, 'type' => 'annual', 'start_date' => '2026-12-01', 'end_date' => '2026-12-01', 'days' => 1, 'status' => 'pending']);
        $this->actingAs($this->admin)->post(route('hr.payroll.store'), ['property_id' => $this->propertyA->id, 'month' => '2026-11', 'currency' => 'RWF']);

        $this->actingAs($this->admin)->delete(route('admin.purge', ['type' => 'property', 'id' => $this->propertyA->id]), ['confirm' => 'DELETE'])
            ->assertRedirect(route('admin.properties.index'));

        $this->assertSame(0, Employee::withTrashed()->where('id', $e->id)->count());
        $this->assertSame(0, Attendance::count());
        $this->assertSame(0, LeaveRequest::count());
        $this->assertSame(0, PayrollRun::count());
        $this->assertSame(0, Payslip::count());
        $this->assertModelExists($kept);
    }
}
