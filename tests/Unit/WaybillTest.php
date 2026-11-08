<?php

namespace Tests\Unit;

use App\Support\Waybill;
use PHPUnit\Framework\TestCase;

class WaybillTest extends TestCase
{
    public function test_parses_typed_numbers(): void
    {
        $this->assertSame(7, Waybill::parse('BB-000007'));
        $this->assertSame(7, Waybill::parse(' bb000007 '));
        $this->assertSame(7, Waybill::parse('#7'));
        $this->assertSame(7, Waybill::parse('7'));
    }

    public function test_parses_the_label_qr_link_a_phone_scanner_reads(): void
    {
        $this->assertSame(7, Waybill::parse('https://boombuy.store/logistics/scan/BB-000007'));
        $this->assertSame(66, Waybill::parse('http://127.0.0.1:8000/logistics/scan/BB-000066/'));
        $this->assertSame(66, Waybill::parse('https://boombuy.store/logistics/scan/BB-000066?x=1'));
    }

    public function test_rejects_what_is_not_a_waybill(): void
    {
        $this->assertNull(Waybill::parse(''));
        $this->assertNull(Waybill::parse('BB-000000'));
        $this->assertNull(Waybill::parse('https://boombuy.store/products/7'));
        $this->assertNull(Waybill::parse('https://boombuy.store/logistics/scan/hello'));
    }
}
