<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\User\Enums\NotificationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property \Carbon\Carbon|null $read_at
 * @property bool $is_important
 */
class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'color',
        'notifiable_type',
        'notifiable_id',
        'data',
        'action_url',
        'action_text',
        'sent_email',
        'sent_push',
        'sent_sms',
        'read_at',
        'sent_at',
        'is_important',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_email' => 'boolean',
        'sent_push' => 'boolean',
        'sent_sms' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_important' => 'boolean',
    ];

    /**
     * Relation avec l'utilisateur.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation polymorphique avec l'entité notifiée.
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Obtenir le type en enum.
     */
    public function getTypeEnum(): NotificationType
    {
        return NotificationType::from($this->type);
    }

    /**
     * Vérifier si la notification est lue.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Marquer comme lue.
     */
    public function markAsRead(): void
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Marquer comme non lue.
     */
    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Obtenir le temps écoulé depuis la notification.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Scope: Non lues.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope: Lues.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope: Importantes.
     */
    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    /**
     * Scope: Par type.
     */
    public function scopeOfType($query, NotificationType $type)
    {
        return $query->where('type', $type->value);
    }

    /**
     * Scope: Récentes.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Créer une notification pour une prédiction.
     */
    public static function createForPrediction(
        int $userId,
        string $title,
        string $message,
        ?int $predictionId = null,
        array $data = []
    ): self {
        $type = NotificationType::PREDICTION;

        return self::create([
            'user_id' => $userId,
            'type' => $type->value,
            'title' => $title,
            'message' => $message,
            'icon' => $type->icon(),
            'color' => $type->color(),
            'notifiable_type' => $predictionId ? 'App\Domains\Prediction\Models\Prediction' : null,
            'notifiable_id' => $predictionId,
            'data' => $data,
            'is_important' => $type->isImportant(),
        ]);
    }

    /**
     * Créer une notification pour un value bet.
     */
    public static function createForValueBet(
        int $userId,
        string $title,
        string $message,
        array $data = []
    ): self {
        $type = NotificationType::VALUE_BET;

        return self::create([
            'user_id' => $userId,
            'type' => $type->value,
            'title' => $title,
            'message' => $message,
            'icon' => $type->icon(),
            'color' => $type->color(),
            'data' => $data,
            'is_important' => $type->isImportant(),
        ]);
    }

    /**
     * Créer une alerte bankroll.
     */
    public static function createBankrollAlert(
        int $userId,
        string $title,
        string $message,
        array $data = []
    ): self {
        $type = NotificationType::BANKROLL_ALERT;

        return self::create([
            'user_id' => $userId,
            'type' => $type->value,
            'title' => $title,
            'message' => $message,
            'icon' => $type->icon(),
            'color' => $type->color(),
            'data' => $data,
            'is_important' => true,
        ]);
    }
}
