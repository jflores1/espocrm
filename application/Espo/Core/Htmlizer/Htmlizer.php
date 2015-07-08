<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

namespace Espo\Core\Htmlizer;

use Espo\ORM\Entity;
use Espo\Core\Exceptions\Error;

use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\DateTime;

require('vendor/zordius/lightncandy/src/lightncandy.php');

class Htmlizer
{
    protected $fileManager;

    protected $dateTime;

    public function __construct(FileManager $fileManager, DateTime $dateTime)
    {
        $this->fileManager = $fileManager;
        $this->dateTime = $dateTime;
    }

    protected function getDataFromEntity(Entity $entity)
    {
        $data = $entity->toArray();

        $fieldDefs = $entity->getFields();
        $fieldList = array_keys($fieldDefs);

        foreach ($fieldList as $field) {
            $type = null;
            if (!empty($fieldDefs[$field]['type'])) {
                $type = $fieldDefs[$field]['type'];
            }
            if ($type == Entity::DATETIME) {
                if (!empty($data[$field])) {
                    $data[$field] = $this->dateTime->convertSystemDateTime($data[$field]);
                }
            } else if ($type == Entity::DATE) {
                if (!empty($data[$field])) {
                    $data[$field] = $this->dateTime->convertSystemDate($data[$field]);
                }
            } else if ($type == Entity::JSON_ARRAY) {
                if (!empty($data[$field])) {
                    $list = $data[$field];
                    $newList = [];
                    foreach ($list as $item) {
                        $v = $item;
                        if ($item instanceof \StdClass) {
                            $v = get_object_vars($v);
                        }
                        $newList[] = $v;
                    }
                    $data[$field] = $newList;
                }
            } else if ($type == Entity::JSON_OBJECT) {
                if (!empty($data[$field])) {
                    $value = $data[$field];
                    if ($value instanceof \StdClass) {
                        $data[$field] = get_object_vars($value);
                    }
                }
            }
        }

        return $data;
    }

    public function render(Entity $entity, $template)
    {
        $code = \LightnCandy::compile($template);
        $id = uniqid('', true);
        $fileName = 'data/cache/template-' . $id;
        $this->fileManager->putContents($fileName, $code);
        $renderer = include($fileName);
        $this->fileManager->removeFile($fileName);

        $data = $this->getDataFromEntity($entity);

        return $renderer($data);
    }
}