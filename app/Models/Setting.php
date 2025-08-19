<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;
    protected $table = "settings";
    public static $entityLabel="Setting";
    protected $fillable = ['set_key','title','description','set_value','group_id','variants','tarif_id'];
    protected static $load_settings = [];
    public static function labels() {
        return ['id'=>'id','set_key'=>'Ключ','title'=>'Заголовок','description'=>'Описание','set_value'=>'Значение','group_id'=>'Группа',
        'group'=>'Группа', 'variants'=>'Варианты выбора', 'tarif_id'=>'Тариф'];
    }
    public static function viewFields() {
        return ['id','set_key','title','set_value','group'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public function group() {
        return $this->hasOne(BotGroup::class, 'id','group_id');
    }
    public function tarif() {
        return $this->hasOne(GroupTarif::class, 'id','tarif_id');
    }
    public function getTitleValueAttribute() {
        if(in_array($this->set_key,['roles','bafs'])) return $this->title;
        return $this->title.": ".$this->set_value;
    }
    public static function groupSettings($group_id) {
        $group = BotGroup::where('id',$group_id)->first();
        //DB::enableQueryLog();
        $baseSets = self::whereNull('group_id')->whereIn('tarif_id',[0,$group->tarif_id])->get();
        //Log::channel('daily')->info("groupSettings sql = ".print_r(DB::getQueryLog(),true));
        $groupSets = self::where('group_id',$group_id)->get();
        $values = [];
        $changingSets = [];
        $basicSets = [];
        foreach($baseSets as $baseSet) {
            $values[$baseSet->set_key] = $baseSet->set_value;
            $changingSets[$baseSet->set_key] = null;
            $basicSets[$baseSet->set_key] = $baseSet;
        }
        foreach($groupSets as $grpSet) {
            $values[$grpSet->set_key] = $grpSet->set_value;
            $changingSets[$grpSet->set_key] = $grpSet;
        }
        $modifBasics = [];  //не сохраняемые копии для построения списка значений
        foreach($baseSets as $baseSet) {
            $setting = new self($baseSet->toArray());
            $setting->set_value = $values[$setting->set_key];
            $modifBasics[] = $setting;
        }
        return ['values'=>$values, 'changingSets'=>$changingSets, 'basicSets'=>$basicSets, 'modifSets'=>$modifBasics];
    }
    public static function groupSetting($group_id, $setting_key) {
        if(!isset(self::$load_settings[$group_id][$setting_key])) {
            $group = BotGroup::where('id',$group_id)->first();
            $baseSet = self::whereNull('group_id')->where('set_key',$setting_key)->whereIn('tarif_id',[0,$group->tarif_id])->first();
            $groupSet = self::where('group_id',$group_id)->where('set_key',$setting_key)->first();
            $value = $groupSet ? $groupSet->set_value : $baseSet->set_value;
            self::$load_settings[$group_id][$setting_key] = ['base'=>$baseSet,'group'=>$groupSet, 'value'=>$value];
        }
        return self::$load_settings[$group_id][$setting_key];
    }
    public static function groupSettingValue($group_id, $setting_key) {
        $grpSets = self::groupSetting($group_id, $setting_key);
        return $grpSets['value'];
    }
    public static function changeGroupSettingValue($group_id, $setting_key,$setting_value) {
        $grpSets = self::groupSetting($group_id, $setting_key);     
        $changeSet = $grpSets['group'] ? $grpSets['group'] : (new self($grpSets['base']->toArray()));
        $changeSet->set_value = $setting_value;
        $changeSet->group_id = $group_id;
        $changeSet->save();
        unset(self::$load_settings[$group_id][$setting_key]);
        return $changeSet;
    }
}