<?php

namespace Tests\Feature;

use Tests\TestCase;

class StatutsTest extends TestCase
{
    public function test_statuts_pdf_is_publicly_downloadable(): void
    {
        $response = $this->get(route('statuts'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
