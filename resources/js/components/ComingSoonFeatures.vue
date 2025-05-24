<template>
  <div class="coming-soon-container">
    <div class="coming-soon-title">بخش‌های آینده</div>
    <div class="coming-soon-features">
      <div 
        class="coming-soon-feature" 
        v-for="feature in features" 
        :key="feature.id"
        @click="showFeatureModal(feature)"
      >
        <div class="coming-soon-badge">بزودی</div>
        <div class="coming-soon-icon">
          <img :src="feature.icon" :alt="feature.title">
        </div>
        <div class="coming-soon-title">{{ feature.title }}</div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ComingSoonFeatures',
  data() {
    return {
      features: [],
      loading: false,
      error: null
    }
  },
  mounted() {
    this.fetchFeatures();
  },
  methods: {
    async fetchFeatures() {
      this.loading = true;
      try {
        const response = await fetch('/api/coming-soon');
        const result = await response.json();
        
        if (result.status === 'success') {
          this.features = result.data;
        } else {
          this.error = 'خطا در دریافت اطلاعات';
        }
      } catch (error) {
        this.error = 'خطا در ارتباط با سرور';
        console.error(error);
      } finally {
        this.loading = false;
      }
    },
    
    async showFeatureModal(feature) {
      // نمایش مودال با پیام
      this.$swal({
        title: feature.title,
        text: 'این بخش در حال پیاده‌سازی می‌باشد',
        icon: 'info',
        confirmButtonText: 'متوجه شدم'
      });
      
      // ارسال درخواست به سرور برای ثبت بازدید
      try {
        await fetch(`/api/coming-soon/${feature.id}/track`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        });
      } catch (error) {
        console.error('خطا در ثبت بازدید:', error);
      }
    }
  }
}
</script>

<style scoped>
.coming-soon-container {
  margin: 30px 0;
}

.coming-soon-title {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 20px;
  text-align: center;
}

.coming-soon-features {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 20px;
}

.coming-soon-feature {
  position: relative;
  background-color: #f8f9fa;
  border-radius: 10px;
  padding: 20px;
  text-align: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  width: 200px;
  cursor: pointer;
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
}
</style> 