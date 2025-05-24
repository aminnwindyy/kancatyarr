<div class="discount-banner" id="discount-banner-{{ $id ?? 'default' }}">
    <div class="discount-content">
        <div class="discount-icon">
            <i class="fas fa-gift"></i>
        </div>
        <div class="discount-info">
            <div class="discount-title">کد تخفیف ویژه</div>
            <div class="discount-code">{{ $code ?? 'WELCOME1403' }}</div>
            <div class="discount-description">برای کپی کردن کد تخفیف کلیک کنید</div>
        </div>
    </div>
    <div class="copy-message" id="copy-message-{{ $id ?? 'default' }}" style="display: none;">
        کد تخفیف کپی شد!
    </div>
</div>

<script>
    document.getElementById('discount-banner-{{ $id ?? 'default' }}').addEventListener('click', function() {
        const discountCode = '{{ $code ?? 'WELCOME1403' }}';
        const copyMessage = document.getElementById('copy-message-{{ $id ?? 'default' }}');
        
        // کپی کردن کد تخفیف به کلیپ‌بورد
        navigator.clipboard.writeText(discountCode)
            .then(() => {
                // نمایش پیام کپی شدن
                copyMessage.style.display = 'flex';
                setTimeout(() => {
                    copyMessage.style.display = 'none';
                }, 2000);
                
                // ثبت رویداد کپی کردن (اختیاری)
                trackDiscountCodeCopy(discountCode);
            })
            .catch(err => {
                console.error('خطا در کپی کردن کد تخفیف:', err);
            });
    });
    
    function trackDiscountCodeCopy(code) {
        // ثبت رویداد کپی کردن کد تخفیف (بدون نیاز به دیتابیس)
        console.log('کد تخفیف کپی شد:', code);
        
        // ارسال به سرور (اختیاری - بدون ذخیره در دیتابیس)
        fetch('/api/discount-codes/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                code: code
            })
        }).catch(error => {
            console.error('خطا در ثبت رویداد کپی کد تخفیف:', error);
        });
    }
</script>

<style>
    .discount-banner {
        position: relative;
        background: linear-gradient(135deg, #ff9966, #ff5e62);
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
        margin: 20px 0;
        overflow: hidden;
    }

    .discount-banner:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

    .discount-content {
        display: flex;
        align-items: center;
    }

    .discount-icon {
        font-size: 2rem;
        margin-left: 15px;
    }

    .discount-info {
        flex: 1;
    }

    .discount-title {
        font-size: 1.2rem;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .discount-code {
        font-size: 1.8rem;
        font-weight: bold;
        letter-spacing: 1px;
        margin-bottom: 5px;
        background-color: rgba(255, 255, 255, 0.2);
        padding: 3px 8px;
        border-radius: 4px;
        display: inline-block;
    }

    .discount-description {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .copy-message {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.2rem;
        font-weight: bold;
    }
</style> 