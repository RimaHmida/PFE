<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresenceJournaliere extends Model
{
    protected $fillable = [
        'affectation_liste_id',
        'employe_id',
        'date',
        'present',
        'recorded_by',
        'validated_by_manager',
    ];

    public function affectationListe()
    {
        return $this->belongsTo(AffectationListe::class);
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
}
