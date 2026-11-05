<?php
namespace Yurstore\ShipEngineAPI;
use Illuminate\Support\ServiceProvider;
class YurstoreShipEngineServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }
	
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/shipengine.php', 'shipengine');
    }
}
?>