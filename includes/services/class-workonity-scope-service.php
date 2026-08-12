<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralises row-level access checks. Capabilities decide what a user may do;
 * this service decides which employee records that capability applies to.
 */
class WORKONITY_Scope_Service {
	public static function current_employee_id() {
		$employee = WORKONITY_Permissions::current_employee();
		return $employee ? (int) $employee->id : 0;
	}

	public static function team_employee_ids( $manager_employee_id = 0, $recursive = true ) {
		global $wpdb;
		$manager_employee_id = $manager_employee_id ?: self::current_employee_id();
		if ( ! $manager_employee_id ) {
			return array();
		}

		$table = WORKONITY_Schema::table( 'employee_managers' );
		$seen  = array();
		$queue = array( (int) $manager_employee_id );
		$depth = 0;
		while ( $queue && $depth < 25 ) {
			$parent   = array_shift( $queue );
			$children = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT employee_id FROM $table WHERE manager_employee_id = %d",
					$parent
				)
			);
			foreach ( $children as $child ) {
				$child = (int) $child;
				if ( ! $child || isset( $seen[ $child ] ) || $child === (int) $manager_employee_id ) {
					continue;
				}
				$seen[ $child ] = true;
				if ( $recursive ) {
					$queue[] = $child;
				}
			}
			++$depth;
		}
		return array_map( 'intval', array_keys( $seen ) );
	}

	public static function employee_ids_for( $module ) {
		$own = self::current_employee_id();
		if ( WORKONITY_Permissions::can( $module . '.view_all' ) || WORKONITY_Permissions::can( $module . '.manage' ) ) {
			return null; // null intentionally means unrestricted by employee id.
		}
		if ( $module === 'leaves' && WORKONITY_Permissions::can( 'approvals.override' ) ) {
			return null;
		}
		if ( WORKONITY_Permissions::can( $module . '.view_team' ) ) {
			return array_values( array_unique( array_merge( $own ? array( $own ) : array(), self::team_employee_ids( $own, true ) ) ) );
		}
		if ( $module === 'leaves' && WORKONITY_Permissions::can( 'leaves.approve' ) ) {
			return array_values( array_unique( array_merge( $own ? array( $own ) : array(), self::team_employee_ids( $own, true ) ) ) );
		}
		return $own ? array( $own ) : array();
	}

	public static function can_access_employee( $employee_id, $module, $action = 'view' ) {
		$employee_id = (int) $employee_id;
		if ( ! $employee_id ) {
			return false;
		}
		if ( WORKONITY_Permissions::can( $module . '.view_all' ) || WORKONITY_Permissions::can( $module . '.manage' ) ) {
			return true;
		}
		if ( $action === 'manage' ) {
			return false;
		}
		$allowed = self::employee_ids_for( $module );
		return is_array( $allowed ) && in_array( $employee_id, $allowed, true );
	}

	public static function sql_filter( $column, $ids, &$params ) {
		if ( $ids === null ) {
			return '';
		}
		$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
		if ( ! $ids ) {
			return ' AND 1=0';
		}
		$params = array_merge( $params, $ids );
		return ' AND ' . $column . ' IN (' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')';
	}
}
