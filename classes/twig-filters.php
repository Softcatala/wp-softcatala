<?php

/**
 * Twig Filters for Softcatalà
 * 
 * Static methods that provide custom Twig filters for the theme.
 * All filters registered via add_to_twig() in functions.php.
 */
class SC_Twig_Filters {

	/**
	 * Safe batch filter - ensures batch size is at least 1 to avoid Twig errors
	 * 
	 * Replicates Twig's batch filter logic but enforces a minimum size of 1
	 * to prevent errors when division operations result in zero or fractional values.
	 * 
	 * @param array $items The array to batch
	 * @param int|float $size The batch size (will be ceiled to nearest integer, minimum 1)
	 * @param mixed $fill Optional value to fill incomplete batches with
	 * @param bool $preserveKeys Whether to preserve array keys (default true)
	 * @return array Array of batches
	 */
	public static function safe_batch( $items, $size, $fill = null, $preserveKeys = true ) {
		if ( ! is_iterable( $items ) ) {
			throw new \RuntimeException( sprintf( 'The "safe_batch" filter expects a sequence or a mapping, got "%s".', gettype( $items ) ) );
		}
		
		$size = max( 1, (int) ceil( $size ) );
		$result = array_chunk( self::to_array( $items, $preserveKeys ), $size, $preserveKeys );
		
		if ( null !== $fill && $result ) {
			$last = count( $result ) - 1;
			if ( $fillCount = $size - count( $result[ $last ] ) ) {
				for ( $i = 0; $i < $fillCount; ++$i ) {
					$result[ $last ][] = $fill;
				}
			}
		}
		
		return $result;
	}

	/**
	 * Convert items to array, matching Twig's toArray behavior
	 * 
	 * @param mixed $items Items to convert (array or Traversable)
	 * @param bool $preserveKeys Whether to preserve keys
	 * @return array
	 */
	private static function to_array( $items, $preserveKeys = true ) {
		if ( $items instanceof \Traversable ) {
			return iterator_to_array( $items, $preserveKeys );
		}
		
		if ( ! is_array( $items ) ) {
			return $items;
		}
		
		return $preserveKeys ? $items : array_values( $items );
	}
}
