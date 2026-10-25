<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelos sincronizáveis entre PC (hub) e celular (client):
 * chave UUID, timestamps e soft delete (exclusões também são sincronizadas).
 */
trait Syncable
{
    use HasUuids;
    use SoftDeletes;
}
