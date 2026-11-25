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

    public function test_embedded_appointment_workflow_never_loads_the_global_pwa_actions(): void
    {
        $embeddedLayout = file_get_contents(dirname(__DIR__, 2) . '/resources/views/layouts/embedded.blade.php');
        $embeddedIndex = file_get_contents(dirname(__DIR__, 2) . '/resources/views/appointments/requests/embedded.blade.php');
        $show = file_get_contents(dirname(__DIR__, 2) . '/resources/views/appointments/requests/show.blade.php');

        $this->assertStringNotContainsString('data-pwa-notifications', $embeddedLayout);
        $this->assertStringNotContainsString('js/pwa.js', $embeddedLayout);
        $this->assertStringContainsString("@extends('layouts.embedded')", $embeddedIndex);
        $this->assertStringContainsString("@extends(\$embedded ? 'layouts.embedded' : 'layouts.app')", $show);
        $this->assertStringContainsString("['embedded' => 1]", $show);
    }

    public function test_core_staff_lists_offer_mobile_cards_instead_of_table_only_navigation(): void
    {
        $requestList = file_get_contents(dirname(__DIR__, 2) . '/resources/views/appointments/requests/partials/content.blade.php');
        $patientList = file_get_contents(dirname(__DIR__, 2) . '/resources/views/livewire/patient-manager.blade.php');
        $appointmentList = file_get_contents(dirname(__DIR__, 2) . '/resources/views/livewire/rendez-vous-manager.blade.php');

        foreach ([$requestList, $patientList, $appointmentList] as $view) {
            $this->assertStringContainsString('md:hidden', $view);
            $this->assertStringContainsString('md:block', $view);
        }
    }

    public function test_pwa_actions_are_grouped_behind_one_compact_toggle(): void
    {
        $staffLayout = file_get_contents(dirname(__DIR__, 2) . '/resources/views/layouts/app.blade.php');
        $publicLayout = file_get_contents(dirname(__DIR__, 2) . '/resources/views/layouts/public.blade.php');
        $pwaScript = file_get_contents(dirname(__DIR__, 2) . '/public/js/pwa.js');

        foreach ([$staffLayout, $publicLayout] as $layout) {
            $this->assertStringContainsString('data-pwa-actions-toggle', $layout);
            $this->assertStringContainsString('data-pwa-actions-menu', $layout);
        }

        $this->assertStringContainsString("document.querySelector('.modal-overlay')", $pwaScript);
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
