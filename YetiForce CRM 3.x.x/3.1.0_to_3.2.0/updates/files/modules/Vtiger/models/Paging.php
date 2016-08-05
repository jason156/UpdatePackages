<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

/**
 * Vtiger Paging Model Class
 */
class Vtiger_Paging_Model extends Vtiger_Base_Model
{

	const DEFAULT_PAGE = 1;
	const PAGE_LIMIT = 20;
	const PAGE_MAX_LIMIT = 9999999;

	/**
	 * Function to get the current page number
	 * @return <Number>
	 */
	function getCurrentPage()
	{
		$currentPage = $this->get('page');
		if (empty($currentPage)) {
			$currentPage = self::DEFAULT_PAGE;
		}
		return $currentPage;
	}

	/**
	 * Function to get the Next page number
	 * @return <Number>
	 */
	function getNextPage()
	{
		$currentPage = $this->get('page');
		if (empty($currentPage)) {
			$currentPage = self::DEFAULT_PAGE;
		}
		return $currentPage + 1;
	}

	/**
	 * Function to get the limit on the number of records per page
	 * @return <Number>
	 */
	function getPageLimit()
	{
		$pageLimit = $this->get('limit');
		if (empty($pageLimit)) {
			$pageLimit = vglobal('list_max_entries_per_page');
			if (empty($pageLimit)) {
				$pageLimit = self::PAGE_LIMIT;
			}
		} elseif ($pageLimit == 'no_limit') {
			$pageLimit = self::PAGE_MAX_LIMIT;
		}
		return (int) $pageLimit;
	}

	function getStartIndex()
	{
		$currentPage = $this->getCurrentPage();
		$pageLimit = $this->getPageLimit();
		return ($currentPage - 1) * $pageLimit;
	}

	/**
	 * Retrieves start sequence number of records in the page
	 * @return <Integer>
	 */
	function getRecordStartRange()
	{
		if ($this->has('range')) {
			$rangeInfo = $this->getRecordRange();
			return $rangeInfo['start'];
		}
		return $this->getPageLimit() * ((int) $this->getCurrentPage() - 1);
	}

	/**
	 * Retrieves end sequence number of records in the page
	 * @return <Integer>
	 */
	function getRecordEndRange()
	{
		if ($this->has('range')) {
			$rangeInfo = $this->getRecordRange();
			return $rangeInfo['end'];
		}
		return $this->getPageLimit() * ((int) $this->getCurrentPage() - 1) + $this->get('noOfEntries');
	}

	/**
	 * Retrieves start and end sequence number of records in the page
	 * @return <array> - array of values
	 * 						- start key which gives start sequence number
	 * 						- end key which gives end sequence number
	 */
	function getRecordRange()
	{
		return $this->get('range');
	}

	/**
	 * Function to specify if previous page exists
	 * @return <Boolean> - true/false
	 */
	public function isPrevPageExists()
	{
		if ($this->has('prevPageExists')) {
			return $this->get('prevPageExists');
		}
		return true;
	}

	/**
	 * Function to specify if next page exists
	 * @return <Boolean> - true/false
	 */
	public function isNextPageExists()
	{
		if ($this->has('nextPageExists')) {
			return $this->get('nextPageExists');
		}
		if ($this->has('noOfEntries')) {
			return $this->get('noOfEntries') == $this->getPageLimit();
		}
		return true;
	}

	/**
	 * calculates page range
	 * @param <type> $recordList - list of records which is show in current page
	 * @return Vtiger_Paging_Model  -
	 */
	function calculatePageRange($recordList)
	{
		$rangeInfo = [];
		$recordCount = count($recordList);
		$pageLimit = $this->getPageLimit();
		if ($recordCount > 0) {
			//specifies what sequencce number of last record in prev page
			$prevPageLastRecordSequence = (($this->getCurrentPage() - 1) * $pageLimit);

			$rangeInfo['start'] = $prevPageLastRecordSequence + 1;
			if ($rangeInfo['start'] == 1) {
				$this->set('prevPageExists', false);
			}
			//Have less number of records than the page limit
			if ($recordCount < $pageLimit) {
				$this->set('nextPageExists', false);
				$rangeInfo['end'] = $prevPageLastRecordSequence + $recordCount;
			} else {
				$rangeInfo['end'] = $prevPageLastRecordSequence + $pageLimit;
			}
			$this->set('range', $rangeInfo);
		} else {
			//Disable previous page only if page is first page and no records exists
			if ($this->getCurrentPage() == 1) {
				$this->set('prevPageExists', false);
			}
			$this->set('nextPageExists', false);
		}
		return $this;
	}

	/**
	 * Function to return info about the number of pages
	 * @return <int> - Number of pages
	 */
	public function getPageCount()
	{
		$pageLimit = $this->getPageLimit();
		if ($this->has('totalCount')) {
			$totalCount = $this->get('totalCount');
			$pageCount = ceil($totalCount / (int) $pageLimit);
		} else {
			$pageCount = $this->get('page');
		}
		if ($pageCount == 0) {
			$pageCount = 1;
		}
		return $pageCount;
	}

	/**
	 * Function to return the page number where pagination begins
	 * @return <int> - number of page
	 */
	public function getStartPagingFrom()
	{
		$pageNumber = $this->get('page');
		$totalCount = $this->get('totalCount');
		$startPaginFrom = $pageNumber - 2;

		if ($pageNumber == $totalCount && 1 != $pageNumber)
			$startPaginFrom = $pageNumber - 4;
		if ($startPaginFrom <= 0 || 1 == $pageNumber)
			$startPaginFrom = 1;

		return $startPaginFrom;
	}
}