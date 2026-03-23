<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\FormulaParserService;

class FormulaParserTest extends TestCase
{
    private FormulaParserService $parser;

    protected function setUp(): void
    {
        $this->parser = new FormulaParserService();
    }

    public function test_basic_math()
    {
        $this->assertEquals(150, $this->parser->calculate("100 + 50"));
        $this->assertEquals(5000, $this->parser->calculate("100 * 50"));
    }

    public function test_variables()
    {
        $vars = ['luas' => 10, 'tarif' => 500];
        $this->assertEquals(5000, $this->parser->calculate("luas * tarif", $vars));
    }

    public function test_simple_if()
    {
        $vars = ['A' => 10, 'B' => 5];
        $this->assertEquals(1, $this->parser->calculate("IF(A > B, 1, 0)", $vars));
        $this->assertEquals(0, $this->parser->calculate("IF(A < B, 1, 0)", $vars));
    }

    public function test_string_comparison()
    {
        $vars = ['tipe' => 'bintang_5'];
        $this->assertEquals(500, $this->parser->calculate("IF(tipe == 'bintang_5', 500, 100)", $vars));
        $this->assertEquals(100, $this->parser->calculate("IF(tipe == 'bintang_3', 500, 100)", $vars));
    }

    public function test_nested_if()
    {
        $vars = ['val' => 2];
        $this->assertEquals(20, $this->parser->calculate("IF(val == 1, 10, IF(val == 2, 20, 30))", $vars));
        
        $vars = ['val' => 3];
        $this->assertEquals(30, $this->parser->calculate("IF(val == 1, 10, IF(val == 2, 20, 30))", $vars));
    }

    public function test_complex_formula()
    {
        $formula = '((njopr + nspr) * IF(panjang * lebar < 1, 1, panjang * lebar) * sisi) * IF(produk == "rokok", 1.1, 1.0) * 0.25';
        $vars = [
            'njopr' => 100000,
            'nspr' => 50000,
            'panjang' => 0.5,
            'lebar' => 0.5,
            'sisi' => 1,
            'produk' => 'rokok'
        ];
        // (150000 * 1 * 1) * 1.1 * 0.25 = 150000 * 1.1 * 0.25 = 165000 * 0.25 = 41250
        $this->assertEquals(41250, $this->parser->calculate($formula, $vars));
    }
}
