<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProjectPdfController extends Controller
{
    public function generate(Project $project)
    {
        // Load all relationships needed by the invoice PDF view.
        $project->load([
            'client',
            'manager',
            'quotations.items',
            'paymentStages.payments.client',
            'variations',
            'payments.client',
        ]);

        // Generate the PDF using the existing invoice Blade template.
        $pdf = Pdf::loadView('pdf.project-invoice', compact('project'));

        // Download the PDF with the project code in the filename.
        return $pdf->download('project-invoice-' . $project->project_code . '.pdf');
    }
}
