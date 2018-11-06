<?php

/**
 * @param $products - массив товаров, формат товара = array ('id'=>123, 'quantity' => 2)
 * @param $user_id
 * @param $city
 * @param $street
 *
 * @return bool|int
 */
function create_order( $products, $user_id, $city, $street ) {
	global $woocommerce;

	if ( 0 === (int) $user_id ) {
		return false;
	}
	if ( ! is_array( $products ) ) {
		return false;
	}

	$current_user      = get_userdata( $user_id );
	$current_user_meta = get_user_meta( (int) $user_id );
	// заполняем данные адреса
	$address = array(
		'first_name' => $current_user->user_firstname,
		'last_name'  => $current_user->user_lastname,
		'email'      => $current_user->user_email,
		'phone'      => $current_user_meta['billing_phone'],
		'address_1'  => $street,
		'city'       => $city,

	);

	if ( ! empty ( $products ) ) {
		// создаем заказ для указанного пользователя
		$order    = wc_create_order( array( 'customer_id' => (int) $current_user->ID ) );
		$order_id = (int) $order->get_id();
		if ( $order_id > 0 ) {

			//  формируем заказ
			foreach ( $products as $product ) {

				// заполняем заказ товарами

				$product_id   = (int) $product['id'];
				$quantity     = (int) $product['quantity'];
				$product_data = wc_get_product( $product['id'] );

				if ( $product_data instanceof WC_Product ) {

					// если id продукта или его количество равно 0 - исключаем из обработки
					if ( $product_id * $quantity === 0 ) {
						continue;
					}

					// добавляем товар в заказ
					// Обрабатываем возможное исключение ( может ли товар быть добавлен в заказ? )
					try {
						$order->add_product( $product_data, $product['quantity'] );
					} catch ( Exception $e ) {
						continue;
					}

				}

			}
			// задаем адрес заказа
			$order->set_address( $address, 'billing' );

			//  обновляем статус, если требуется
			$order->update_status( "Completed", 'Imported order', true );

			// считаем сумму заказа
			$order->calculate_totals();
			// сохраняем ордер
			$order->save();

			return $order_id;
		}


	}
}