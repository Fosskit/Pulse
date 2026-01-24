<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodeGenerator extends Model
{
    protected $fillable = [
        'entity',
        'prefix',
        'format',
        'current_sequence',
        'reset_yearly',
        'reset_monthly',
        'padding',
    ];

    protected $casts = [
        'reset_yearly' => 'boolean',
        'reset_monthly' => 'boolean',
        'current_sequence' => 'integer',
        'padding' => 'integer',
    ];

    /**
     * Generate the next code for this entity
     */
    public function generateCode(): string
    {
        // Check if we need to reset sequence
        if ($this->shouldResetSequence()) {
            $this->current_sequence = 0;
        }

        // Increment sequence
        $this->increment('current_sequence');
        $this->refresh();

        // Parse format and replace placeholders
        $code = $this->format;
        
        $replacements = [
            '{prefix}' => $this->prefix ?? '',
            '{year}' => date('Y'),
            '{year2}' => date('y'),
            '{month}' => date('m'),
            '{day}' => date('d'),
            '{seq}' => str_pad($this->current_sequence, $this->padding, '0', STR_PAD_LEFT),
        ];

        // Handle dynamic padding like {seq:5}
        $code = preg_replace_callback('/\{seq:(\d+)\}/', function ($matches) {
            return str_pad($this->current_sequence, (int)$matches[1], '0', STR_PAD_LEFT);
        }, $code);

        foreach ($replacements as $placeholder => $value) {
            $code = str_replace($placeholder, $value, $code);
        }

        return $code;
    }

    /**
     * Check if sequence should be reset based on settings
     */
    protected function shouldResetSequence(): bool
    {
        if (!$this->reset_yearly && !$this->reset_monthly) {
            return false;
        }

        $lastUpdate = $this->updated_at;
        $now = now();

        if ($this->reset_yearly && $lastUpdate->year !== $now->year) {
            return true;
        }

        if ($this->reset_monthly && ($lastUpdate->year !== $now->year || $lastUpdate->month !== $now->month)) {
            return true;
        }

        return false;
    }
}
