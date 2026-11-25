<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class UiContractTest extends TestCase
{
    public function test_livewire_views_do_not_use_legacy_binding_syntax(): void
    {
        $contents = $this->livewireViewContents();

        $this->assertStringNotContainsString('wire:model.defer', $contents);
        $this->assertStringNotContainsString('wire:model.debounce', $contents);
        $this->assertStringNotContainsString('@this.', $contents);
        $this->assertStringNotContainsString('Livewire.emit', $contents);
        $this->assertStringNotContainsString('dispatchBrowserEvent', $contents);
    }

    public function test_livewire_dialogs_use_the_shared_accessible_modal_component(): void
    {
        $directory = dirname(__DIR__, 2) . '/resources/views/livewire';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            $this->assertDoesNotMatchRegularExpression(
                '/class=["\'][^"\']*fixed\s+inset-0[^"\']*(?:z-|bg-black)/',
                $contents,
                "Legacy modal overlay found in {$file->getFilename()}"
            );
        }
    }

    public function test_dashboard_modal_actions_do_not_collide_with_their_state_properties(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/livewire/accueil-patient.blade.php');

        $this->assertStringContainsString('wire:click="openCaisseOperations"', $view);
        $this->assertStringContainsString('wire:click="openStatistiques"', $view);
        $this->assertStringNotContainsString('wire:click="showCaisseOperations"', $view);
        $this->assertStringNotContainsString('wire:click="showStatistiques"', $view);
    }

    private function livewireViewContents(): string
    {
        $directory = dirname(__DIR__, 2) . '/resources/views/livewire';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $contents = '';

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $contents .= file_get_contents($file->getPathname());
            }
        }

        return $contents;
    }
}
