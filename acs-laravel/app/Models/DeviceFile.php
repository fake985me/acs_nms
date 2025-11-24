<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceFile extends Model
{
    protected $fillable = [
        'name',
        'file_type',
        'file_path',
        'file_size',
        'version',
        'manufacturer',
        'model',
        'description',
    ];

    public function getFileSizeHumanAttribute()
    {
        if (!$this->file_size) return 'N/A';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getTr069FileTypeAttribute()
    {
        // TR-069 Download RPC file types
        $types = [
            'firmware' => '1 Firmware Upgrade Image',
            'web_content' => '2 Web Content',
            'vendor_config' => '3 Vendor Configuration File',
            'vendor_log' => '4 Vendor Log File',
        ];
        
        return $types[$this->file_type] ?? '1 Firmware Upgrade Image';
    }
}
