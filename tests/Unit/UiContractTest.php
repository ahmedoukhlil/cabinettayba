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

    public function test_pending_requests_badge_is_anchored_to_a_notification_bell(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/livewire/accueil-patient.blade.php');

        $this->assertStringContainsString('notification-bell', $view);
        $this->assertStringContainsString('notification-badge', $view);
        $this->assertStringContainsString('fas fa-bell', $view);
        $this->assertStringNotContainsString('absolute -right-2 -top-2', $view);
    }

    public function test_escape_only_closes_the_topmost_modal(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/modal.blade.php');

        $this->assertStringContainsString("modals[modals.length - 1] === \$el", $component);
        $this->assertStringContainsString('x-ref="close"', $component);
        $this->assertStringNotContainsString('wire:keydown.escape.window', $component);
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
