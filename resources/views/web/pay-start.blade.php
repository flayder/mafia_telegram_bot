<!DOCTYPE html>
<html>
<head>
    <title>Продажа товара</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Стили для адаптивности под мобильный экран */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .product-image img {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
        }
        .product-details {
            margin-top: 20px;
        }
        .product-details h2 {
            margin-top: 0;
        }
        .payment-options {
            margin-top: 20px;
        }
        .payment-options label {
            display: block;
            margin-bottom: 10px;
        }
        .payment-options input[type="radio"] {
            margin-right: 10px;
        }
        .buy-button {
            display: block;
            width: 80%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
            margin-top: 20px;
        }
    </style>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body>
    <form class="container" method="POST" action="/create-pay">
        @csrf
        <input type="hidden" name="offer_id" value="{{ $offer->id }}" >        
        <input type="hidden" id="user_id" name="user_id" value="0" >
        <!--
        <div class="product-image">
            <img src="https://via.placeholder.com/300x300" alt="Продукт">
        </div> 
        -->
        <div class="product-details">
            <h2>{{ $offer->name }}</h2>            
            <p>Цена: {{ $offer->price }} $</p>
        <!--    <p>К оплате: {{ $rub_price }} RUB</p>  -->
        </div>
        <!--
        <div class="payment-options">
            <h3>Выберите способ оплаты:</h3>
            <label>
                <input type="radio" name="payment-method" value="credit-card"> Кредитная карта
            </label>
            <label>
                <input type="radio" name="payment-method" value="paypal"> PayPal
            </label>
            <label>
                <input type="radio" name="payment-method" value="bank-transfer"> Банковский перевод
            </label>
        </div>
        -->
        <button type="submit"  class="buy-button">Оплатить</button>       
    </form>
    <script>
        var webApp = window.Telegram.WebApp;
        var webAppData = webApp.initDataUnsafe;
        document.querySelector('#user_id').value = webAppData.user.id;   
        webApp.expand();
    </script>
</body>
</html>