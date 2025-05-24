<div class="coming-soon-feature" data-feature-id="{{ $id }}">
    <div class="coming-soon-badge">بزودی</div>
    <div class="coming-soon-icon">
        <img src="{{ $icon }}" alt="{{ $title }}">
    </div>
    <div class="coming-soon-title">{{ $title }}</div>
    <div class="coming-soon-cta">
        <button class="coming-soon-button" onclick="showComingSoonModal('{{ $id }}')">مشاهده</button>
    </div>
</div>

<script>
    function showComingSoonModal(featureId) {
        // نمایش مودال با پیام "این بخش در حال پیاده‌سازی می‌باشد"
        Swal.fire({
            title: 'بزودی',
            text: 'این بخش در حال پیاده‌سازی می‌باشد',
            icon: 'info',
            confirmButtonText: 'متوجه شدم'
        });
        
        // ارسال درخواست به سرور برای ثبت بازدید
        fetch(`/api/coming-soon/${featureId}/track`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
    }
</script>

<style>
    .coming-soon-feature {
        position: relative;
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        max-width: 200px;
        margin: 10px;
    }
    
    .coming-soon-feature:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
    
    .coming-soon-badge {
        position: absolute;
        top: -10px;
        right: -10px;
        background-color: #ff6b6b;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: bold;
    }
    
    .coming-soon-icon {
        margin-bottom: 15px;
    }
    
    .coming-soon-icon img {
        width: 64px;
        height: 64px;
    }
    
    .coming-soon-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 15px;
    }
    
    .coming-soon-button {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .coming-soon-button:hover {
        background-color: #45a049;
    }
</style> 