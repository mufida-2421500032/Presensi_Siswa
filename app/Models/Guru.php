<?php

namespace App\Models;

use Database\Factories\GuruFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'nip', 'nama', 'no_telepon'])]
class Guru extends Model
{
    /** @use HasFactory<GuruFactory> */
    use HasFactory;

    protected $table = 'guru';

    public function emailAkun(): ?string
    {
        return User::where('id', $this->user_id)->value('email');
    }

    public function kelasDiampu(): array
    {
        return Jadwal::where('guru_id', $this->id)->distinct()->orderBy('kelas')->pluck('kelas')->all();
    }
}