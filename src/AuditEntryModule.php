<?php

namespace ruturajmaniyar\mod\audit;

use yii\base\Module;

class AuditEntryModule extends Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'ruturajmaniyar\mod\audit\controllers';

    /**
     * @var string Connection ID
     */
    public $db = 'db';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }


}