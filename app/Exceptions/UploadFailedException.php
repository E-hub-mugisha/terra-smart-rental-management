<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;

/**
 * A file could not be moved into its upload folder. Rendered as a normal
 * form error so the user can simply try again.
 */
class UploadFailedException extends RuntimeException
{
    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage(), 'errors' => ['file' => [$this->getMessage()]]], 422);
        }

        return back()->withInput($request->except(['password', 'password_confirmation']))->withErrors(['file' => $this->getMessage()]);
    }

    public function report(): bool
    {
        // Logged once with context; the user sees a friendly message.
        logger()->warning('Upload failed: ' . $this->getMessage(), ['previous' => $this->getPrevious()?->getMessage()]);

        return true;
    }
}
