<?php

namespace Service;

use Core\Service;
use Model\Group as GroupModel;
use Db\Group\GetByUserId;
use Db\Group\GetById;
use Db\Group\Store;

class Group extends Service
{

	/**
	 * @param $userId
	 * @return GroupModel[]
	 * @throws \Core\Query\NoResultException
	 */
	public static function getByUserId($userId)
	{
		$query = new GetByUserId();
		$query->setUserId($userId);

		return self::fillCollection(new GroupModel(), $query->fetchAll());
	}

	/**
	 * @param $id
	 * @return GroupModel
	 * @throws \Core\Query\NoResultException
	 */
	public static function getById($id)
	{
		$query = new GetById();
		$query->setId($id);

		return self::fillModel(new GroupModel(), $query->fetchRow());
	}

	/**
	 * @param GroupModel $group
	 * @return int
	 */
	public static function store(GroupModel $group)
	{
		$query = new Store();
		$query->setId($group->getId());
		$query->setName($group->getName());
		$query->setType($group->getType());
		$query->setCreated($group->getCreated());

		if ($group->getId()) {
			$query->query();
			return (int)$group->getId();
		} else {
			return (int)$query->insert();
		}
	}

}