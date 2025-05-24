<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>نمونه بنر تخفیف</title>
    <!-- استایل‌های فونت‌آوسوم برای آیکون‌ها -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Tahoma', 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        .example-section {
            margin-top: 30px;
            padding: 20px;
            border: 1px dashed #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        
        .title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>نمونه‌های استفاده از بنر تخفیف</h1>
        
        <div class="example-section">
            <div class="title">نمونه ۱: استفاده از کامپوننت Blade</div>
            <p>با استفاده از کامپوننت Blade، می‌توانید به راحتی یک بنر تخفیف به صفحه خود اضافه کنید:</p>
            
            <!-- استفاده از کامپوننت Blade -->
            @include('components.discount-banner', ['code' => 'WELCOME1403', 'id' => 'example1'])
        </div>
        
        <div class="example-section">
            <div class="title">نمونه ۲: استفاده با کد تخفیف متفاوت</div>
            <p>می‌توانید کد تخفیف دلخواه خود را به کامپوننت ارسال کنید:</p>
            
            <!-- استفاده از کامپوننت با کد متفاوت -->
            @include('components.discount-banner', ['code' => 'SUMMER1403', 'id' => 'example2'])
        </div>
        
        <div class="example-section">
            <div class="title">نمونه ۳: استفاده از کامپوننت Vue (در صورت استفاده از Vue در پروژه)</div>
            <p>اگر در پروژه خود از Vue استفاده می‌کنید، می‌توانید از کامپوننت Vue نیز استفاده کنید:</p>
            
            <div id="vue-app">
                <discount-banner code="SPECIAL50"></discount-banner>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script>
        // تنها در صورتی که از Vue در پروژه استفاده می‌کنید
        if (typeof Vue !== 'undefined') {
            Vue.component('discount-banner', {
                template: `
                    <div class="discount-banner" @click="copyDiscountCode">
                        <div class="discount-content">
                            <div class="discount-icon">
                                <i class="fas fa-gift"></i>
                            </div>
                            <div class="discount-info">
                                <div class="discount-title">کد تخفیف ویژه</div>
                                <div class="discount-code">{{ discountCode }}</div>
                                <div class="discount-description">برای کپی کردن کد تخفیف کلیک کنید</div>
                            </div>
                        </div>
                        <div class="copy-message" v-if="showCopyMessage">
                            کد تخفیف کپی شد!
                        </div>
                    </div>
                `,
                props: {
                    code: {
                        type: String,
                        default: 'WELCOME1403'
                    }
                },
                data() {
                    return {
                        discountCode: this.code,
                        showCopyMessage: false
                    }
                },
                methods: {
                    copyDiscountCode() {
                        navigator.clipboard.writeText(this.discountCode)
                            .then(() => {
                                this.showCopyMessage = true;
                                setTimeout(() => {
                                    this.showCopyMessage = false;
                                }, 2000);
                            })
                            .catch(err => {
                                console.error('خطا در کپی کردن کد تخفیف:', err);
                            });
                    }
                }
            });
            
            new Vue({
                el: '#vue-app'
            });
        }
    </script>
</body>
</html>