<?php 

namespace App\Http\Controllers; 

use App\Models\InventoryStorage; 
use Illuminate\Http\Request; 

class InventoryStorageController extends Controller 
{ 
    public function dashboard() 
    { 
        $items = InventoryStorage::latest()->get(); 
        return view('InventoryStorage.dashboard', compact('items')); 
    } 

    public function defaultList()
    {
        $items = InventoryStorage::latest()->get();
        return view('InventoryStorage.defaultList', compact('items'));
{
    $items = InventoryStorage::all();

    return view('inventory-storage.dashboard', compact('items'));
}
    }

    public function store(Request $request) 
    { 
        $validated = $request->validate([ 
            'id' => 'required|integer|unique:inventory_storages',
            'emergecnySupplies' => 'required|integer|min:0',
            'rescueSupplies' => 'required|integer|min:0',
            'quantity' => 'required|integer|min:0', 
            'status' => 'required|string', 
            'location' => 'nullable|string|max:255', 
            'fund_source' => 'nullable|string|max:255', 
            'date_received' => 'nullable|date', 
            'date_checked' => 'nullable|date', 
        ]); 

        $data = $validated; 
        InventoryStorage::create($data); 

        return redirect()->back()->with('success', 'Inventory item added successfully!'); 
    } 
}
