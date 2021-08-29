<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Theme;

class Themes extends Controller
{
    private $fonts = [];

    public function __construct() {
        $this->fonts = [
            "MyriadPro-Regular.woff",
            "MyriadPro-Light.woff",
            "MyriadPro-Bold.woff",
            "MyriadPro_Regular.woff",
            "MyriadPro_Light.woff",
            "MyriadPro_BoldCond.woff",
            "MyriadPro_Bold.woff",
            "HelveticaNeueLTStd_HvCn.woff",
            "HelveticaNeueLTStd_Cn.woff",
            "HelveticaNeueLTStd_BdCn.woff",
            "BasicBullets.woff"
        ];
    }
    public function index() {
        $shopify_themes = $this->getAllThemes();
        $themes = array_map(function ($theme) {
            return $theme['shopify_theme_id'];
        }, Theme::select('shopify_theme_id')->get()->toArray());
        
        $shopify_theme_ids = array();

        foreach($shopify_themes as $shopify_theme) {
            if(in_array($shopify_theme['id'], $themes)) {
                Theme::where('shopify_theme_id', $shopify_theme['id'])
                    ->update(['name' => $shopify_theme['name'], 'role' => $shopify_theme['role']]);
            } else {
                $theme = new Theme;

                $theme->shopify_theme_id = $shopify_theme['id'];
                $theme->name = $shopify_theme['name'];
                $theme->role = $shopify_theme['role'];
                $theme->installed = 0;

                $theme->save();
            }
            array_push($shopify_theme_ids, $shopify_theme['id']);
        }
        
        foreach($themes as $theme_id) {
            if(!in_array($theme_id, $shopify_theme_ids)) {
                Theme::where('shopify_theme_id', $theme_id)->delete();
            }
        }
        
        $installed_themes = array_map(function ($theme) {
            return $theme['shopify_theme_id'];
        }, Theme::select('shopify_theme_id')->where('installed', 1)->get()->toArray());

        $theme_ids = array_map(function ($theme) {
            return $theme['shopify_theme_id'];
        }, Theme::select('shopify_theme_id')->get()->toArray());

        $themes = array_map(function ($theme) {
            return ['value' => $theme['shopify_theme_id'], 'label' => $theme['name']];
        }, Theme::all()->toArray());

        return response()->json(['themes' => $themes, 'installed' => $installed_themes, 'theme_ids' => $theme_ids]);
    }

    public function save(Request $request) {
        $new_theme_ids = $request->all()['selected'];
        $old_theme_ids = array_map(function ($theme) {
            return $theme['shopify_theme_id'];
        }, Theme::select('shopify_theme_id')->where('installed', 1)->get()->toArray());
        $theme_ids = array_unique(array_merge($new_theme_ids, $old_theme_ids));

        foreach($old_theme_ids as $theme_id) {
            Theme::where('shopify_theme_id', $theme_id)
                    ->update(['installed' => 0]);
        }

        foreach($new_theme_ids as $theme_id) {
            Theme::where('shopify_theme_id', $theme_id)
                    ->update(['installed' => 1]);
        }

        foreach($theme_ids as $theme_id) {
            if(in_array($theme_id, $new_theme_ids) && in_array($theme_id, $old_theme_ids)) {
                continue;
            } else if(in_array($theme_id, $new_theme_ids) && !in_array($theme_id, $old_theme_ids)) {
                $this->installTheme($theme_id);
            } else if(!in_array($theme_id, $new_theme_ids) && in_array($theme_id, $old_theme_ids)) {
                $this->uninstallTheme($theme_id);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Themes have been updated successfully.']);
    }

    public function getAllThemes() {
        $shop = Auth::user();
        $shopApi = $shop->api();

        $themes = $shopApi->rest('GET', 'admin/themes.json')['body']['container']['themes'];
        
        return $themes;
    }

    public function installTheme($id) {
        $shop = Auth::user();
        $shopApi = $shop->api();

        $html = view('snippets.fastland')->render();
        $html = str_replace('%app_domain%', env('APP_URL'), $html);
        $html = str_replace('%%%', "}}", $html);
        $html = str_replace('%%', "{{", $html);
        $response = $shopApi->rest('PUT', 'admin/themes/' . $id . '/assets.json', [
            "asset" => [
                "key" => "snippets/fastland.liquid",
                "value" => $html
            ]
        ]);

        foreach($this->fonts as $font) {
            $response = $shopApi->rest('PUT', 'admin/themes/' . $id . '/assets.json', [
                "asset" => [
                    "key" => "assets/".$font,
                    "src" => "https://cod.ag/fastland/fonts/".$font
                ]
            ]);
        }

        return true;
    }

    public function uninstallTheme($id) {
        $shop = Auth::user();
        $shopApi = $shop->api();

        $response = $shopApi->rest('DELETE', 'admin/themes/' . $id . '/assets.json', [
            "asset" => [
                "key" => "snippets/fastland.liquid"
            ]
        ]);

        foreach($this->fonts as $font) {
            $response = $shopApi->rest('DELETE', 'admin/themes/' . $id . '/assets.json', [
                "asset" => [
                    "key" => "assets/".$font
                ]
            ]);
        }

        return true;
    }
}
