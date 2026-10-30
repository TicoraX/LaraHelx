<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * I8 (05 §2): cambiar la contraseña cierra las otras sesiones, invalida "Recordarme" y revoca los
     * tokens de API, todo en una transacción. $keepSession es la sesión actual (null al restablecer).
     */
    public function changePassword(string $password, ?string $keepSession = null): void
    {
        DB::transaction(function () use ($password, $keepSession) {
            $this->forceFill(['password' => $password])->setRememberToken(Str::random(60));
            $this->save();
            DB::table('sessions')->where('user_id', $this->id)
                ->when($keepSession, fn ($q) => $q->where('id', '!=', $keepSession))->delete();
            $this->tokens()->delete();
        });
    }

    public function artists(): HasMany
    {
        return $this->hasMany(Artist::class);
    }

    /** El dueño de un álbum es el de su artista (05 §1). */
    public function albums(): HasManyThrough
    {
        return $this->hasManyThrough(Album::class, Artist::class);
    }
}
