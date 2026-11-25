<?php

namespace Tests\Unit;

use App\Models\DetailFacturePatient;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ModelAutoloadContractTest extends TestCase
{
    public function test_invoice_detail_model_respects_psr4_filename_casing(): void
    {
        $reflection = new ReflectionClass(DetailFacturePatient::class);

        $this->assertSame('DetailFacturePatient', $reflection->getShortName());
        $this->assertSame('DetailFacturePatient.php', basename($reflection->getFileName()));
    }

    public function test_invoice_views_do_not_reference_the_legacy_class_casing(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/livewire/reglement-facture.blade.php');

        $this->assertStringNotContainsString('App\\Models\\Detailfacturepatient', $view);
        $this->assertStringNotContainsString('App\\Models\\DetailFacturePatient', $view);
        $this->assertStringContainsString('$facture->details', $view);
    }
}
