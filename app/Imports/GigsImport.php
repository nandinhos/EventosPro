<?php

namespace App\Imports;

use App\Models\Gig;
use Maatwebsite\Excel\Concerns\ToModel;

class GigsImport implements ToModel
{
    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Gig([
            //
        ]);
    }
}
