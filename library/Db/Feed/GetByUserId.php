<?php

namespace Db\Feed;

use Core\Query;

class GetByUserId extends Query
{

	protected $parentPostId;
	protected $userId;

	protected function build()
	{
		$query = '
			SELECT
				p.id,
				p.root_post_id,
				p.parent_post_id,
				p.user_id,
				p.group_id,
				p.visibility,
				p.content,
				p.image_file_id,
				p.attachment_file_id,
				p.created,
				p.modified,
				u.first_name,
				u.last_name,
				IFNULL(u.portrait_file_id, cnfg.value) AS portrait_file_id,
				IF(IFNULL(ml.user_id, 0) = 0, 0, 1) AS liked,
				IFNULL(al.like_count, 0) AS likes,
				COUNT(sp.id) AS comments
			FROM
				posts p
			JOIN users u ON u.id = p.user_id
			JOIN configs cnfg ON cnfg.key = \'default_portrait_id\'
			LEFT JOIN posts sp ON sp.parent_post_id = p.id
			LEFT JOIN likes ml ON ml.post_id = p.id AND ml.user_id = ?
			LEFT JOIN (
				SELECT
					post_id,
					COUNT(*) AS like_count
				FROM
					likes
				GROUP BY
					post_id
			) al ON al.post_id = p.id
			LEFT JOIN relations r1 ON r1.user_id = p.user_id AND r1.user_id2 = ? AND r1.confirmed IS NOT NULL
			LEFT JOIN relations r2 ON r2.user_id = ? AND r2.user_id2 = p.user_id AND r2.confirmed IS NOT NULL
			LEFT JOIN user_groups ug ON ug.group_id = p.group_id AND ug.user_id = ? AND ug.confirmed IS NOT NULL
			WHERE
				(
					(p.visibility = \'public\' OR p.visibility = \'comment\')
				OR	(p.visibility = \'contacts\' AND (r1.user_id IS NOT NULL OR r2.user_id IS NOT NULL))
				OR	(p.visibility = \'me\' AND p.user_id = ?)
				OR 	(p.visibility = \'group\' AND ug.user_id IS NOT NULL)
			)';

		$this->addBind($this->userId);
		$this->addBind($this->userId);
		$this->addBind($this->userId);
		$this->addBind($this->userId);
		$this->addBind($this->userId);

		if ($this->parentPostId) {
			$query .= '
				AND p.parent_post_id = ?';

			$this->addBind($this->parentPostId);
		} else {
			$query .= '
				AND
					p.parent_post_id IS NULL';
		}

		$query .= '
			GROUP BY
				p.id
			ORDER BY
				p.created DESC
			LIMIT 10';

		return $query;
	}

	/**
	 * @param mixed $parentPostId
	 */
	public function setParentPostId($parentPostId)
	{
		$this->parentPostId = $parentPostId;
	}

	/**
	 * @param mixed $userId
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

}