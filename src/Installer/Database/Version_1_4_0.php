<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

final class Version_1_4_0 extends AbstractMigration
{
    public const SESSIONS_TABLE = 'inpostizi_basket_session';
    private const CART_ID_FK = self::SESSIONS_TABLE . '-cart_id';
    private const ORDER_ID_FK = self::SESSIONS_TABLE . '-order_id';
    private const CART_ID_IDX = 'cart_id_unique';
    private const BASKET_ID_IDX = 'basket_id_unique';

    public function getVersion(): string
    {
        return '1.4.0';
    }

    public function up(): bool
    {
        if (!$this->tableExists(self::SESSIONS_TABLE)) {
            return $this->createSessionTable();
        }

        return $this->cleanupSessionsTable()
            && $this->dropColumn(self::SESSIONS_TABLE, 'basket_cached')
            && $this->dropColumn(self::SESSIONS_TABLE, 'event')
            && $this->changeColumn(self::SESSIONS_TABLE, 'session_id', 'INT(10) UNSIGNED NOT NULL')
            && $this->changeColumn(self::SESSIONS_TABLE, 'cart_id', 'VARCHAR(255) NOT NULL')
            && $this->changeColumn(self::SESSIONS_TABLE, 'order_id', 'INT(10) UNSIGNED')
            && $this->changeColumn(self::SESSIONS_TABLE, 'redirected', 'TINYINT(1) NOT NULL DEFAULT 0')
            && $this->addForeignKey(self::SESSIONS_TABLE, 'cart', ['session_id'], ['id_cart'], self::CART_ID_FK, [
                'onDelete' => 'CASCADE',
            ])
            && $this->addForeignKey(self::SESSIONS_TABLE, 'orders', ['order_id'], ['id_order'], self::ORDER_ID_FK, [
                'onDelete' => 'CASCADE',
            ])
            && $this->addUniqueIndex(self::SESSIONS_TABLE, ['session_id'], self::CART_ID_IDX)
            && $this->addUniqueIndex(self::SESSIONS_TABLE, ['cart_id'], self::BASKET_ID_IDX);
    }

    private function createSessionTable(): bool
    {
        return $this->db->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::SESSIONS_TABLE . '` (
                id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                session_id INT(10) UNSIGNED NOT NULL,
                cart_id VARCHAR(255) NOT NULL,
                confirmation_response TEXT,
                order_id INT(10) UNSIGNED,
                order_details TEXT,
                redirect_url VARCHAR(255),
                basket_cache TEXT,
                coupons TEXT,
                redirected TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                FOREIGN KEY `' . self::CART_ID_FK . '` (`session_id`)
                    REFERENCES `' . _DB_PREFIX_ . 'cart` (`id_cart`)
                    ON DELETE CASCADE,
                FOREIGN KEY `' . self::ORDER_ID_FK . '` (`order_id`)
                    REFERENCES `' . _DB_PREFIX_ . 'orders` (`id_order`)
                    ON DELETE CASCADE,
                CONSTRAINT `' . self::CART_ID_IDX . '` UNIQUE (`session_id`),
                CONSTRAINT `' . self::BASKET_ID_IDX . '` UNIQUE (`cart_id`)
            )
            ENGINE = ' . _MYSQL_ENGINE_ . '
            CHARSET = utf8
            COLLATE = utf8_general_ci;
        ');
    }

    private function cleanupSessionsTable(): bool
    {
        return $this->deleteUnassociatedSessions()
            && $this->updateNonExistentOrderIds()
            && $this->deleteDuplicatedCartAssociations()
            && $this->deleteDuplicatedIdentifiers();
    }

    private function deleteUnassociatedSessions(): bool
    {
        return $this->db->execute('
            DELETE bs
            FROM `' . _DB_PREFIX_ . self::SESSIONS_TABLE . '` bs
            LEFT JOIN  `' . _DB_PREFIX_ . 'cart` c
                ON c.id_cart = bs.session_id
            WHERE c.id_cart IS NULL
        ');
    }

    private function updateNonExistentOrderIds(): bool
    {
        return $this->db->execute('
            UPDATE `' . _DB_PREFIX_ . self::SESSIONS_TABLE . '` bs
            LEFT JOIN  `' . _DB_PREFIX_ . 'orders` o
                ON o.id_order = bs.order_id
            SET bs.order_id = NULL
            WHERE o.id_order IS NULL
        ');
    }

    private function deleteDuplicatedCartAssociations(): bool
    {
        if (!$this->db->delete(self::SESSIONS_TABLE, 'confirmation_response IS NULL AND order_id IS NULL')) {
            return false;
        }

        $sql = (new \DbQuery())
            ->select('session_id, COUNT(*) - 1 AS `limit`')
            ->from(self::SESSIONS_TABLE)
            ->groupBy('session_id')
            ->having('COUNT(*) > 1');

        $data = $this->db->executeS($sql);

        if (false === $data) {
            return false;
        }

        $result = true;

        foreach ($data as $row) {
            $result &= $this->db->execute('
                DELETE FROM `' . _DB_PREFIX_ . self::SESSIONS_TABLE . '`
                WHERE session_id = ' . (int) $row['session_id'] . '
                ORDER BY confirmation_response IS NULL
                LIMIT ' . (int) $row['limit']
            );
        }

        return (bool) $result;
    }

    private function deleteDuplicatedIdentifiers(): bool
    {
        $sql = (new \DbQuery())
            ->select('cart_id, COUNT(*) - 1 AS `limit`')
            ->from(self::SESSIONS_TABLE)
            ->groupBy('cart_id')
            ->having('COUNT(*) > 1');

        $data = $this->db->executeS($sql);

        if (false === $data) {
            return false;
        }

        $result = true;

        foreach ($data as $row) {
            $result &= $this->db->execute('
                DELETE FROM `' . _DB_PREFIX_ . self::SESSIONS_TABLE . '`
                WHERE cart_id = "' . $row['cart_id'] . '"
                ORDER BY confirmation_response IS NULL
                LIMIT ' . (int) $row['limit']
            );
        }

        return (bool) $result;
    }
}
