<?php

namespace App\Http\Controllers;

use App\Models\TaxObject;
use Illuminate\Http\Request;

class BillboardAuditController extends Controller
{
    public function uploadPhoto(Request $request, TaxObject $taxObject)
    {
        $request->validate([
            'photo' => 'required|image|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('billboards', 'public');
            $taxObject->update([
                'last_photo_url' => $path,
                'installation_date' => $taxObject->installation_date ?? now(),
            ]);
        }

        return response()->json([
            'message' => 'Photo uploaded successfully',
            'path' => $taxObject->last_photo_url,
            'is_new' => $taxObject->is_new_billboard
        ]);
    }

    public function verify(TaxObject $taxObject)
    {
        $taxObject->update(['is_verified_physically' => true]);

        return response()->json([
            'message' => 'Billboard verified physically',
            'status' => 'success'
        ]);
    }
}
