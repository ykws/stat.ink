<?php
/**
 * @copyright Copyright (C) 2015-2018 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

declare(strict_types=1);

namespace app\components\widgets;

use DateTimeImmutable;
use DateTimeZone;
use Yii;
use app\components\i18n\Formatter;
use yii\base\Widget;
use yii\helpers\Html;

class TimestampColumnWidget extends Widget
{
    public $value;
    public $showTZ = true;
    public $showRelative = false;
    public $formatter;

    public function init()
    {
        parent::init();

        if (!$this->formatter) {
            $this->formatter = Yii::createObject([
                'class' => Formatter::class,
                'nullDisplay' => '',
            ]);
        } elseif (is_array($this->formatter)) {
            $this->formatter = Yii::createObject($this->formatter);
        }
    }

    public function run()
    {
        $f = $this->formatter;

        if ($this->value === null) {
            return $f->asText(null);
        }

        return implode(' ', array_filter([
            $f->asHtmlDatetime($this->value),
            $this->showTZ
                ? Html::a(
                    Html::encode(
                        (new DateTimeImmutable())
                            ->setTimestamp((int)$f->asTimestamp($this->value))
                            ->setTimezone(new DateTimeZone(Yii::$app->timeZone))
                            ->format('T')
                    ),
                    'javascript:;',
                    [
                        'role' => 'button',
                        'aria-haspopup' => 'true',
                        'aria-expanded' => 'false',
                        'data' => [
                            'toggle' => 'modal',
                            'target' => '#timezone-dialog',
                        ],
                    ]
                )
                : null,
            $this->showRelative
                ? Html::encode('(' . $f->asRelativeTime($this->value) . ')')
                : null,
        ]));
    }
}
