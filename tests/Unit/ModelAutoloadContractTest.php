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
}
