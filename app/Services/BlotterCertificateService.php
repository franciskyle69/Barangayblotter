<?php

namespace App\Services;

use App\Models\BlotterRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BlotterCertificateService
{
    /**
     * Generates the certificate PDF, stores it, and returns the public path.
     */
    public function generateAndStore(BlotterRequest $request): string
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        if (!$tenant) {
            throw new RuntimeException('Tenant context is required to generate a certificate.');
        }

        $request->loadMissing(['incident', 'requestedBy', 'reviewedBy', 'tenant']);

        if (!$request->verification_code) {
            $request->verification_code = Str::upper(Str::random(10));
        }

        $pdf = Pdf::loadView('certificates.blotter-request', [
            'tenant' => $tenant,
            'blotterRequest' => $request,
            'incident' => $request->incident,
            'requestedBy' => $request->requestedBy,
            'reviewedBy' => $request->reviewedBy,
        ])->setPaper('a4');

        $dir = 'certificates/blotter-requests';
        $filename = sprintf(
            'blotter-request-%s-%s.pdf',
            $request->id,
            strtolower($request->verification_code)
        );

        $path = $dir . '/' . $filename;
        Storage::disk('public')->put($path, $pdf->output());

        return 'storage/' . $path;
    }
}

