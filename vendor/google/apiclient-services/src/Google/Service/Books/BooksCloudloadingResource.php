<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

class Google_Service_Books_BooksCloudloadingResource extends Google_Model
{
    public $author;
    public $processingState;
    public $title;
    public $volumeId;

    public function setAuthor($author)
    {
        $this->author = $author;
    }
    public function getAuthor()
    {
        return $this->author;
    }
    public function setProcessingState($processingState)
    {
        $this->processingState = $processingState;
    }
    public function getProcessingState()
    {
        return $this->processingState;
    }
    public function setTitle($title)
    {
        $this->title = $title;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function setVolumeId($volumeId)
    {
        $this->volumeId = $volumeId;
    }
    public function getVolumeId()
    {
        return $this->volumeId;
    }
}
