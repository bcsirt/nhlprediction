<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Prediction\Enums;

use App\Domains\Prediction\Enums\ModelType;
use PHPUnit\Framework\TestCase;

class ModelTypeTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $cases = ModelType::cases();

        $this->assertCount(9, $cases);
        $this->assertEquals('random_forest', ModelType::RANDOM_FOREST->value);
        $this->assertEquals('gradient_boosting', ModelType::GRADIENT_BOOSTING->value);
        $this->assertEquals('xgboost', ModelType::XGBOOST->value);
        $this->assertEquals('neural_network', ModelType::NEURAL_NETWORK->value);
        $this->assertEquals('logistic_regression', ModelType::LOGISTIC_REGRESSION->value);
        $this->assertEquals('svm', ModelType::SVM->value);
        $this->assertEquals('ensemble', ModelType::ENSEMBLE->value);
        $this->assertEquals('statistical', ModelType::STATISTICAL->value);
        $this->assertEquals('ai_powered', ModelType::AI_POWERED->value);
    }

    public function test_label_returns_french_labels(): void
    {
        $this->assertEquals('Forêt Aléatoire', ModelType::RANDOM_FOREST->label());
        $this->assertEquals('XGBoost', ModelType::XGBOOST->label());
        $this->assertEquals('Réseau de Neurones', ModelType::NEURAL_NETWORK->label());
        $this->assertEquals('Régression Logistique', ModelType::LOGISTIC_REGRESSION->label());
        $this->assertEquals('IA (Claude)', ModelType::AI_POWERED->label());
    }

    public function test_framework_returns_correct_values(): void
    {
        $this->assertEquals('scikit-learn', ModelType::RANDOM_FOREST->framework());
        $this->assertEquals('scikit-learn', ModelType::GRADIENT_BOOSTING->framework());
        $this->assertEquals('scikit-learn', ModelType::LOGISTIC_REGRESSION->framework());
        $this->assertEquals('scikit-learn', ModelType::SVM->framework());
        $this->assertEquals('xgboost', ModelType::XGBOOST->framework());
        $this->assertEquals('pytorch', ModelType::NEURAL_NETWORK->framework());
        $this->assertEquals('statsmodels', ModelType::STATISTICAL->framework());
        $this->assertEquals('anthropic', ModelType::AI_POWERED->framework());
    }

    public function test_complexity_returns_valid_range(): void
    {
        foreach (ModelType::cases() as $type) {
            $complexity = $type->complexity();
            $this->assertGreaterThanOrEqual(1, $complexity);
            $this->assertLessThanOrEqual(5, $complexity);
        }
    }

    public function test_complexity_values(): void
    {
        $this->assertEquals(1, ModelType::LOGISTIC_REGRESSION->complexity());
        $this->assertEquals(1, ModelType::STATISTICAL->complexity());
        $this->assertEquals(2, ModelType::RANDOM_FOREST->complexity());
        $this->assertEquals(2, ModelType::SVM->complexity());
        $this->assertEquals(3, ModelType::GRADIENT_BOOSTING->complexity());
        $this->assertEquals(3, ModelType::XGBOOST->complexity());
        $this->assertEquals(4, ModelType::NEURAL_NETWORK->complexity());
        $this->assertEquals(5, ModelType::ENSEMBLE->complexity());
        $this->assertEquals(5, ModelType::AI_POWERED->complexity());
    }

    public function test_training_time_returns_non_empty_string(): void
    {
        foreach (ModelType::cases() as $type) {
            $this->assertNotEmpty($type->trainingTime());
            $this->assertIsString($type->trainingTime());
        }
    }

    public function test_interpretability_returns_valid_values(): void
    {
        $validValues = ['Haute', 'Moyenne', 'Faible', 'Très faible', 'Moyenne (explications textuelles)'];

        foreach (ModelType::cases() as $type) {
            $this->assertContains($type->interpretability(), $validValues);
        }
    }

    public function test_supports_probabilities(): void
    {
        $this->assertTrue(ModelType::RANDOM_FOREST->supportsProbabilities());
        $this->assertTrue(ModelType::LOGISTIC_REGRESSION->supportsProbabilities());
        $this->assertTrue(ModelType::NEURAL_NETWORK->supportsProbabilities());
        $this->assertFalse(ModelType::SVM->supportsProbabilities());
    }

    public function test_requires_scaling(): void
    {
        $this->assertTrue(ModelType::LOGISTIC_REGRESSION->requiresScaling());
        $this->assertTrue(ModelType::SVM->requiresScaling());
        $this->assertTrue(ModelType::NEURAL_NETWORK->requiresScaling());
        $this->assertFalse(ModelType::RANDOM_FOREST->requiresScaling());
        $this->assertFalse(ModelType::XGBOOST->requiresScaling());
    }

    public function test_supports_feature_importance(): void
    {
        $this->assertTrue(ModelType::RANDOM_FOREST->supportsFeatureImportance());
        $this->assertTrue(ModelType::GRADIENT_BOOSTING->supportsFeatureImportance());
        $this->assertTrue(ModelType::XGBOOST->supportsFeatureImportance());
        $this->assertFalse(ModelType::LOGISTIC_REGRESSION->supportsFeatureImportance());
        $this->assertFalse(ModelType::NEURAL_NETWORK->supportsFeatureImportance());
    }

    public function test_default_hyperparameters_random_forest(): void
    {
        $params = ModelType::RANDOM_FOREST->defaultHyperparameters();

        $this->assertArrayHasKey('n_estimators', $params);
        $this->assertArrayHasKey('max_depth', $params);
        $this->assertArrayHasKey('random_state', $params);
        $this->assertEquals(100, $params['n_estimators']);
        $this->assertEquals(42, $params['random_state']);
    }

    public function test_default_hyperparameters_xgboost(): void
    {
        $params = ModelType::XGBOOST->defaultHyperparameters();

        $this->assertArrayHasKey('n_estimators', $params);
        $this->assertArrayHasKey('learning_rate', $params);
        $this->assertArrayHasKey('max_depth', $params);
        $this->assertEquals(0.1, $params['learning_rate']);
    }

    public function test_default_hyperparameters_logistic_regression(): void
    {
        $params = ModelType::LOGISTIC_REGRESSION->defaultHyperparameters();

        $this->assertArrayHasKey('C', $params);
        $this->assertArrayHasKey('max_iter', $params);
        $this->assertEquals(1.0, $params['C']);
        $this->assertEquals(1000, $params['max_iter']);
    }

    public function test_default_hyperparameters_empty_for_some_types(): void
    {
        $this->assertEmpty(ModelType::NEURAL_NETWORK->defaultHyperparameters());
        $this->assertEmpty(ModelType::STATISTICAL->defaultHyperparameters());
        $this->assertEmpty(ModelType::AI_POWERED->defaultHyperparameters());
    }

    public function test_neural_network_is_most_complex_after_ensemble(): void
    {
        $this->assertEquals(4, ModelType::NEURAL_NETWORK->complexity());
        $this->assertEquals(5, ModelType::ENSEMBLE->complexity());
    }
}
