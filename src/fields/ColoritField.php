<?php
namespace fruitstudios\colorit\fields;

use fruitstudios\colorit\Colorit;
use fruitstudios\colorit\models\Color;
use fruitstudios\colorit\models\PaletteColor;
use fruitstudios\colorit\helpers\ColorHelper;
use fruitstudios\colorit\web\assets\colorit\ColoritAssetBundle;

use Craft;
use craft\web\View;
use craft\base\ElementInterface;
use craft\base\Element;
use craft\base\Field;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\validators\ColorValidator;
use craft\validators\ArrayValidator;

use yii\db\Schema;

/**
 * @author    Fruit Studios
 * @package   Palette
 * @since     1.0.0
 */
class ColoritField extends Field
{
    // Public Properties
    // =========================================================================

    public $presetMode = false;

    public $presetId;
    public $paletteColors;
    public $paletteBaseColors;
    public $allowCustomColor = false;
    public $allowOpacity = false;
    public $colorFormat = 'auto';
    public $siteSettings = [];

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('colorit', 'Colorit');
    }

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
    }

    public function setPresetMode(bool $on)
    {
        $this->presetMode = $on;
    }

    public function rules()
    {
        $rules = [];
        $rules[] = ['paletteColors', 'validatePaletteColors'];
        $rules[] = ['paletteBaseColors', ArrayValidator::class];
        $rules[] = ['colorFormat', 'string'];
        $rules[] = ['colorFormat', 'default', 'value' => 'auto'];
        $rules[] = [['allowCustomColor', 'allowOpacity'], 'boolean'];
        $rules[] = [['allowCustomColor', 'allowOpacity'], 'default', 'value' => false];

        if($this->presetMode)
        {
            return $rules;
        }
        return array_merge(parent::rules(), $rules);
    }

    public function validatePaletteColors()
    {
        $usedHandles = [];
        foreach ($this->paletteColors as $i => $paletteColor)
        {
            $_paletteColor = new PaletteColor($paletteColor);

            if($_paletteColor->handle ?? false)
            {
                $isHandleUnique = !in_array($_paletteColor->handle, $usedHandles);
                if(!$isHandleUnique)
                {
                    $this->addError('paletteColors', Craft::t('colorit', 'Row {row} {error}', [
                        'row' => ($i + 1),
                        'error' => Craft::t('colorit', 'handle must be unique'),
                    ]));
                }
                $usedHandles[] = $_paletteColor->handle;
            }

            if(!$_paletteColor->validate() || !$isHandleUnique)
            {
                $this->paletteColors[$i] = [
                    'label' => [
                        'value' => $_paletteColor->label ?? '',
                        'hasErrors' => $_paletteColor->hasErrors('label') ?? '',
                    ],
                    'handle' => [
                        'value' => $_paletteColor->handle ?? '',
                        'hasErrors' => !$isHandleUnique ? true : $_paletteColor->hasErrors('handle') ?? '',
                    ],
                    'color' => [
                        'value' => $_paletteColor->color ?? '',
                        'hasErrors' => $_paletteColor->hasErrors('color') ?? '',
                    ],
                ];
                foreach ($_paletteColor->getErrors() as $error)
                {
                    $this->addError('paletteColors', Craft::t('colorit', 'Row {row} {error}', [
                        'row' => ($i + 1),
                        'error' => lcfirst($error[0]),
                    ]));
                }
            }
        }
    }


    public function getType(): string
    {
        return get_class($this);
    }

    public function getElementValidationRules(): array
    {
        return ['validateColorValue'];
    }

    public function validateColorValue(ElementInterface $element)
    {
        if ($element->getScenario() === Element::SCENARIO_LIVE)
        {
            $fieldValue = $element->getFieldValue($this->handle);
            if($fieldValue && !$fieldValue->validate())
            {
                $element->addModelErrors($fieldValue, $this->handle);
            }
        }
    }

    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    public function isValueEmpty($value, ElementInterface $element): bool
    {
        return empty($value->handle ?? '');
    }

    public function normalizeValue($value, ElementInterface $element = null)
    {
        if($value instanceof Color)
        {
            return $value;
        }

        if(is_string($value))
        {
            $value = Json::decodeIfJson($value);
        }

        if (isset($value['handle']))
        {
            $color = new Color();
            $color = Craft::configure($color, $value);
            $this->_populateWithPreset();
            $color->field = $this;
            return $color;
        }
        return $value;
    }

    public function serializeValue($value, ElementInterface $element = null)
    {
        $serialized = [];
        if($value instanceof Color)
        {
            $serialized = [
                'handle' => $value->handle,
                'custom' => $value->custom,
                'opacity' => $value->opacity,
            ];
        }
        return parent::serializeValue($serialized, $element);
    }

    /**
     * Creates a copy of the field settings and stores them in a site specific array
     * @author Josh Smith <me@joshsmith.dev>
     * @return array
     */
    public function getSettings(): array
    {
        $data = parent::getSettings();
        $currentSite = Craft::$app->sites->getCurrentSite();

        // Create a site specific copy of the settings
        $settings = $data; unset($settings['siteSettings']);
        $data['siteSettings'][$currentSite->id] = $settings;

        // Get the existing field settings if this isn't a new field
        $fieldSettings = [];
        if( !$this->getIsNew() ){
            $field = Craft::$app->getFields()->getFieldById($this->id);
            $fieldSettings = $field->siteSettings ?? [];
        }

        // Merge the site settings
        $data['siteSettings'] = ($fieldSettings + $data['siteSettings']);

        return $data;
    }

    public function getSettingsHtml()
    {
        $field = $this;
        $presets = [];
        $presetOptions[] =  [
            'value' => '',
            'label' => 'No Preset'
        ];

        // Apply multi-site settinsg
        $this->_populateWithSiteSettings();

        if(!$this->presetMode)
        {
            $presets = Colorit::$plugin->getPresets()->getAllPresetsByType(self::class);
            if($presets)
            {
                foreach ($presets as $preset)
                {
                    $presetOptions[] = [
                        'value' => $preset->id,
                        'label' => $preset->name,
                    ];
                }
            }
        }

        return Craft::$app->getView()->renderTemplate('colorit/_fields/colorit/settings', compact(
            'field',
            'presets',
            'presetOptions'
        ));
    }

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $this->_populateWithSiteSettings();
        $this->_populateWithPreset();
        $view = Craft::$app->getView();
        $id = $view->formatInputId($this->handle);
        $namespacedId = Craft::$app->view->namespaceInputId($id);

        $view->registerAssetBundle(ColoritAssetBundle::class);
        $js = Json::encode([
            'id' => $id,
            'namespacedId' => $namespacedId,
            'name' => $this->handle,
            'debug' => Craft::$app->getConfig()->getGeneral()->devMode,
        ]);
        $view->registerJs('new Colorit('.$js.');', View::POS_END);

        return $view->renderTemplate('colorit/_fields/colorit/input', [
            'id' => $id,
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    public function getInputPreviewHtml(): string
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(ColoritAssetBundle::class);

        return $view->renderTemplate('colorit/_fields/colorit/preview', [
            'field' => $this,
        ]);
    }

    public function getPalette()
    {
        $palette = [];
        if($this->paletteBaseColors)
        {
            $palette = ColorHelper::baseColors($this->paletteBaseColors);
        }

        if($this->paletteColors)
        {
            foreach($this->paletteColors as $paletteColor)
            {
                $palette[$paletteColor['handle']] = $paletteColor;
            }
        }
        return $palette;
    }

    // Static Methods
    // =========================================================================

    /**
     * Populates this field object with site specific settings
     * @author Josh Smith <me@joshsmith.dev>
     * @return void
     */
    private function _populateWithSiteSettings()
    {
        if( empty($this->siteSettings) ) return;

        $currentSite = Craft::$app->sites->getCurrentSite();
        $settings = $this->siteSettings[$currentSite->id] ?? [];

        foreach ($settings as $key => $value) {
            if( property_exists($this, $key) ){
                $this->$key = $value;
            }
        }
    }

    private function _populateWithPreset()
    {
        if($this->presetId)
        {
            $preset = Colorit::$plugin->getPresets()->getPresetById($this->presetId);
            if($preset)
            {
                Craft::configure($this, $preset->getSettings());
            }
        }
    }

}
