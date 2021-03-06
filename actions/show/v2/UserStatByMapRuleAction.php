<?php
/**
 * @copyright Copyright (C) 2015-2017 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

namespace app\actions\show\v2;

use Yii;
use app\models\Battle2FilterForm;
use app\models\Map2;
use app\models\Rule2;
use app\models\User;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\ViewAction as BaseAction;

class UserStatByMapRuleAction extends BaseAction
{
    public function run()
    {
        $request = Yii::$app->getRequest();
        $user = User::findOne(['screen_name' => $request->get('screen_name')]);
        if (!$user) {
            throw new NotFoundHttpException(Yii::t('app', 'Could not find user'));
        }

        $filter = new Battle2FilterForm();
        $filter->load($_GET);
        $filter->screen_name = $user->screen_name;
        $filter->validate();

        return $this->controller->render('user-stat-by-map-rule', array_merge(
            [
                'user' => $user,
                'filter' => $filter,
            ],
            $this->getData($user, $filter)
        ));
    }

    private function getData(User $user, Battle2FilterForm $filter)
    {
        $query = $user->getBattle2s()
            ->orderBy(null)
            ->andWhere(['in', '{{battle2}}.{{is_win}}', [true, false]])
            ->applyFilter($filter)
            ->innerJoinWith(['map', 'rule'], false)
            ->select([
                'map_key' => 'MAX({{map2}}.[[key]])',
                'rule_key' => 'MAX({{rule2}}.[[key]])',
                'result' => sprintf('(CASE %s END)', implode(' ', [
                    "WHEN {{battle2}}.[[is_win]] = TRUE THEN 'win'",
                    "ELSE 'lose'",
                ])),
                'count' => 'COUNT(*)',
            ])
            ->groupBy([
                '{{battle2}}.[[map_id]]',
                '{{battle2}}.[[rule_id]]',
                '{{battle2}}.[[is_win]]',
            ]);
        
        $maps = Map2::find()->all();
        $rules = Rule2::find()->orderBy(['id' => SORT_ASC])->all();

        $ret = ['total' => []];
        foreach ($rules as $rule) {
            $ret['total'][$rule->key] = [
                'win' => 0,
                'lose' => 0,
            ];
        }
        foreach ($maps as $map) {
            $tmp = [];
            foreach ($rules as $rule) {
                $tmp[$rule->key] = [
                    'win' => 0,
                    'lose' => 0,
                ];
            }
            $ret[$map->key] = $tmp;
        }

        foreach ($query->createCommand()->queryAll() as $row) {
            $row = (object)$row;
            $ret[$row->map_key][$row->rule_key][$row->result] = (int)$row->count;
            $ret['total'][$row->rule_key][$row->result] += (int)$row->count;
        }

        $maps2 = ArrayHelper::map(
            $maps,
            'key',
            function (Map2 $map) : string {
                return Yii::t('app-map2', $map->name);
            }
        );
        uksort($maps2, function (string $key1, string $key2) use ($maps2) : int {
            if ($key1 === 'mystery') {
                return 1;
            }
            if ($key2 === 'mystery') {
                return -1;
            }
            return strnatcasecmp($maps2[$key1], $maps2[$key2]);
        });

        $rules2 = ArrayHelper::map(
            $rules,
            'key',
            function (Rule2 $rule) : string {
                return Yii::t('app-rule2', $rule->name);
            }
        );

        return [
            'data' => $ret,
            'mapNames' => $maps2,
            'ruleNames' => $rules2,
        ];
    }
}
