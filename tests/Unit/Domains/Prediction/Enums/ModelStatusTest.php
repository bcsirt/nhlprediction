<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Prediction\Enums;

use App\Domains\Prediction\Enums\ModelStatus;
use PHPUnit\Framework\TestCase;

class ModelStatusTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $cases = ModelStatus::cases();

        $this->assertCount(6, $cases);
        $this->assertEquals('training', ModelStatus::TRAINING->value);
        $this->assertEquals('validating', ModelStatus::VALIDATING->value);
        $this->assertEquals('testing', ModelStatus::TESTING->value);
        $this->assertEquals('production', ModelStatus::PRODUCTION->value);
        $this->assertEquals('deprecated', ModelStatus::DEPRECATED->value);
        $this->assertEquals('failed', ModelStatus::FAILED->value);
    }

    public function test_label_returns_french_labels(): void
    {
        $this->assertEquals('En entraînement', ModelStatus::TRAINING->label());
        $this->assertEquals('En validation', ModelStatus::VALIDATING->label());
        $this->assertEquals('En test', ModelStatus::TESTING->label());
        $this->assertEquals('En production', ModelStatus::PRODUCTION->label());
        $this->assertEquals('Obsolète', ModelStatus::DEPRECATED->label());
        $this->assertEquals('Échec', ModelStatus::FAILED->label());
    }

    public function test_color_returns_correct_colors(): void
    {
        $this->assertEquals('blue', ModelStatus::TRAINING->color());
        $this->assertEquals('cyan', ModelStatus::VALIDATING->color());
        $this->assertEquals('yellow', ModelStatus::TESTING->color());
        $this->assertEquals('green', ModelStatus::PRODUCTION->color());
        $this->assertEquals('gray', ModelStatus::DEPRECATED->color());
        $this->assertEquals('red', ModelStatus::FAILED->color());
    }

    public function test_icon_returns_correct_icons(): void
    {
        $this->assertEquals('🔨', ModelStatus::TRAINING->icon());
        $this->assertEquals('🔍', ModelStatus::VALIDATING->icon());
        $this->assertEquals('🧪', ModelStatus::TESTING->icon());
        $this->assertEquals('✅', ModelStatus::PRODUCTION->icon());
        $this->assertEquals('⚠️', ModelStatus::DEPRECATED->icon());
        $this->assertEquals('❌', ModelStatus::FAILED->icon());
    }

    public function test_can_predict(): void
    {
        $this->assertTrue(ModelStatus::TESTING->canPredict());
        $this->assertTrue(ModelStatus::PRODUCTION->canPredict());
        $this->assertFalse(ModelStatus::TRAINING->canPredict());
        $this->assertFalse(ModelStatus::VALIDATING->canPredict());
        $this->assertFalse(ModelStatus::DEPRECATED->canPredict());
        $this->assertFalse(ModelStatus::FAILED->canPredict());
    }

    public function test_is_active(): void
    {
        $this->assertTrue(ModelStatus::TRAINING->isActive());
        $this->assertTrue(ModelStatus::VALIDATING->isActive());
        $this->assertTrue(ModelStatus::TESTING->isActive());
        $this->assertTrue(ModelStatus::PRODUCTION->isActive());
        $this->assertFalse(ModelStatus::DEPRECATED->isActive());
        $this->assertFalse(ModelStatus::FAILED->isActive());
    }

    public function test_is_production(): void
    {
        $this->assertTrue(ModelStatus::PRODUCTION->isProduction());
        $this->assertFalse(ModelStatus::TRAINING->isProduction());
        $this->assertFalse(ModelStatus::TESTING->isProduction());
        $this->assertFalse(ModelStatus::DEPRECATED->isProduction());
    }

    public function test_next_status_workflow(): void
    {
        $this->assertEquals(ModelStatus::VALIDATING, ModelStatus::TRAINING->nextStatus());
        $this->assertEquals(ModelStatus::TESTING, ModelStatus::VALIDATING->nextStatus());
        $this->assertEquals(ModelStatus::PRODUCTION, ModelStatus::TESTING->nextStatus());
        $this->assertNull(ModelStatus::PRODUCTION->nextStatus());
        $this->assertNull(ModelStatus::DEPRECATED->nextStatus());
        $this->assertNull(ModelStatus::FAILED->nextStatus());
    }

    public function test_description_returns_non_empty_string(): void
    {
        foreach (ModelStatus::cases() as $status) {
            $this->assertNotEmpty($status->description());
            $this->assertIsString($status->description());
        }
    }

    public function test_workflow_progression(): void
    {
        $current = ModelStatus::TRAINING;
        $expectedPath = [
            ModelStatus::VALIDATING,
            ModelStatus::TESTING,
            ModelStatus::PRODUCTION,
        ];

        foreach ($expectedPath as $expected) {
            $current = $current->nextStatus();
            $this->assertEquals($expected, $current);
        }

        $this->assertNull($current->nextStatus());
    }

    public function test_only_production_is_green(): void
    {
        foreach (ModelStatus::cases() as $status) {
            if ($status === ModelStatus::PRODUCTION) {
                $this->assertEquals('green', $status->color());
            } else {
                $this->assertNotEquals('green', $status->color());
            }
        }
    }

    public function test_terminal_states_have_no_next_status(): void
    {
        $terminalStates = [
            ModelStatus::PRODUCTION,
            ModelStatus::DEPRECATED,
            ModelStatus::FAILED,
        ];

        foreach ($terminalStates as $state) {
            $this->assertNull($state->nextStatus());
        }
    }
}
