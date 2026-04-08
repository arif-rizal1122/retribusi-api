<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SimpadKoneksiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SimpadKoneksiController extends Controller
{
    protected $service;

    public function __construct(SimpadKoneksiService $service)
    {
        $this->service = $service;
    }

    /**
     * Get legacy taxpayer by NPWPD
     */
    public function getTaxpayer($npwpd)
    {
        $legacy = $this->service->getLegacyTaxpayer($npwpd);
        
        if (!$legacy) {
            return Response::json(['message' => 'Wajib Pajak tidak ditemukan di sistem legacy'], 404);
        }

        $mapped = $this->service->mapTaxpayer($legacy);

        return Response::json([
            'success' => true,
            'source' => 'SW_PATDA',
            'data' => $mapped
        ]);
    }

    /**
     * Get legacy officers
     */
    public function getOfficers()
    {
        $legacy = $this->service->getLegacyOfficers();
        
        $mapped = $legacy->map(function ($item) {
            return $this->service->mapOfficer($item);
        });

        return Response::json([
            'success' => true,
            'source' => 'SW_PATDA',
            'count' => count($mapped),
            'data' => $mapped
        ]);
    }

    /**
     * Get legacy objects by tax type (hotel, restoran, reklame)
     */
    public function getObjects($type)
    {
        try {
            $legacy = $this->service->getLegacyObjects($type);
            
            $mapped = $legacy->map(function ($item) use ($type) {
                return $this->service->mapTaxObject($item, $type);
            });

            return Response::json([
                'success' => true,
                'source' => 'SW_PATDA',
                'type' => $type,
                'hierarchy_level' => 'Objek',
                'count' => count($mapped),
                'data' => $mapped
            ]);
        } catch (\Exception $e) {
            return Response::json(['message' => 'Gagal mengambil data objek: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sync legacy object to modern M-PAD (Simplified for migration tool)
     */
    public function syncObject(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'legacy_id' => 'required',
            'retribution_type_id' => 'required|exists:retribution_types,id',
        ]);

        // Logic sync would go here, inserting into tax_objects table
        // For now, return the mapped data that would be synced
        
        $type = $request->type;
        $legacy = $this->service->getLegacyObjects($type)
            ->where('CPM_ID', $request->legacy_id)
            ->first();

        if (!$legacy) {
            return Response::json(['message' => 'Data legacy tidak ditemukan'], 404);
        }

        $mapped = $this->service->mapTaxObject($legacy, $type);
        $mapped['retribution_type_id'] = $request->retribution_type_id;
        $mapped['migration_flag'] = true;

        return Response::json([
            'success' => true,
            'message' => 'Data siap dimigrasi ke database baru',
            'preview' => $mapped
        ]);
    }
}
