<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PosReceiptReturnTest extends TestCase
{
    public function test_receipt_completion_returns_once_without_resubmitting_payment(): void
    {
        $node = $this->nodeBinary();
        $script = dirname(__DIR__).DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'pos-receipt-return.test.js';
        $this->assertFileExists($script);

        $command = escapeshellarg($node).' '.escapeshellarg($script).' 2>&1';
        $output = [];
        $code = 1;
        exec($command, $output, $code);

        $this->assertSame(0, $code, implode(PHP_EOL, $output));
        $this->assertStringContainsString('pos-receipt-return tests passed', implode(PHP_EOL, $output));
    }

    private function nodeBinary(): string
    {
        $candidates = ['node', 'node.exe'];
        foreach ($candidates as $candidate) {
            $output = [];
            $code = 1;
            exec(escapeshellarg($candidate).' -v 2>&1', $output, $code);
            if ($code === 0) {
                return $candidate;
            }
        }

        $this->fail('Node.js is required to run the receipt-return regression script.');
    }
}
