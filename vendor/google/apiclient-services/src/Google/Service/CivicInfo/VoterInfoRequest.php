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

class Google_Service_CivicInfo_VoterInfoRequest extends Google_Model
{
    protected $contextParamsType = 'Google_Service_CivicInfo_ContextParams';
    protected $contextParamsDataType = '';
    protected $voterInfoSegmentResultType = 'Google_Service_CivicInfo_VoterInfoSegmentResult';
    protected $voterInfoSegmentResultDataType = '';

    /**
   * @param Google_Service_CivicInfo_ContextParams
   */
    public function setContextParams(Google_Service_CivicInfo_ContextParams $contextParams)
    {
        $this->contextParams = $contextParams;
    }
    /**
   * @return Google_Service_CivicInfo_ContextParams
   */
    public function getContextParams()
    {
        return $this->contextParams;
    }
    /**
   * @param Google_Service_CivicInfo_VoterInfoSegmentResult
   */
    public function setVoterInfoSegmentResult(Google_Service_CivicInfo_VoterInfoSegmentResult $voterInfoSegmentResult)
    {
        $this->voterInfoSegmentResult = $voterInfoSegmentResult;
    }
    /**
   * @return Google_Service_CivicInfo_VoterInfoSegmentResult
   */
    public function getVoterInfoSegmentResult()
    {
        return $this->voterInfoSegmentResult;
    }
}
