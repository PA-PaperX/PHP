import { useRouter } from 'vue-router'
import { driver } from 'driver.js'

let isTourActive = false

export const useTour = () => {
  const router = useRouter()

  // Custom animation and styling for the cursor
  const injectCursorCSS = () => {
    if (document.getElementById('tour-cursor-style')) return
    const style = document.createElement('style')
    style.id = 'tour-cursor-style'
    style.innerHTML = `
      @keyframes clickBounce {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(-8px, -8px) scale(0.9); }
      }
      .tour-cursor {
        position: fixed;
        width: 40px;
        height: 40px;
        z-index: 99999999;
        pointer-events: none;
        transition: all 0.5s ease-in-out;
        opacity: 0;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23F97316' stroke='white' stroke-width='1.5'%3E%3Cpath d='M3.10578 3.5186C2.86872 2.6583 3.82772 1.93661 4.59313 2.40018L21.4391 12.5996C22.2536 13.0927 22.0945 14.3391 21.1687 14.6169L14.7369 16.5464L10.6019 23.4357C10.1292 24.2233 8.92209 23.9575 8.79093 23.0363L7.85412 16.4566L2.36195 14.4984C1.48839 14.1868 1.18956 13.0135 1.83842 12.4414L3.10578 3.5186Z' /%3E%3C/svg%3E");
        background-size: contain;
        background-repeat: no-repeat;
        animation: clickBounce 1.5s infinite;
        filter: drop-shadow(2px 4px 6px rgba(0,0,0,0.3));
      }
      .driver-popover {
        border-radius: 12px;
        padding: 20px;
        font-family: 'Prompt', sans-serif;
      }
      .driver-popover-title {
        font-size: 18px;
        font-weight: 700;
        color: #F97316;
      }
      .driver-popover-description {
        font-size: 14px;
        color: #4b5563;
      }
      .driver-popover-next-btn, .driver-popover-prev-btn {
        border-radius: 6px;
        font-family: 'Prompt', sans-serif;
      }
      .driver-popover-next-btn {
        background-color: #F97316 !important;
        text-shadow: none !important;
        color: white !important;
        border: none !important;
      }
    `
    document.head.appendChild(style)
  }

  const createCursor = () => {
    let cursor = document.getElementById('tour-cursor')
    if (!cursor) {
      cursor = document.createElement('div')
      cursor.id = 'tour-cursor'
      cursor.className = 'tour-cursor'
      document.body.appendChild(cursor)
    }
    return cursor
  }

  const updateCursorPosition = (element: Element | undefined) => {
    const cursor = document.getElementById('tour-cursor')
    if (!cursor || !element) return
    
    const rect = element.getBoundingClientRect()
    // Position cursor at bottom right of the element
    cursor.style.top = `${rect.bottom - 10}px`
    cursor.style.left = `${rect.right - 10}px`
    cursor.style.opacity = '1'
  }

  const hideCursor = () => {
    const cursor = document.getElementById('tour-cursor')
    if (cursor) {
      cursor.style.opacity = '0'
      setTimeout(() => cursor.remove(), 500)
    }
  }

  const startUserTour = async () => {
    if (!import.meta.client) return
    if (localStorage.getItem('tour_completed_user')) return
    if (isTourActive) return

    isTourActive = true

    injectCursorCSS()
    createCursor()

    const routesUser = [
      '/dashboard',
      '/report-issue',
      '/inventory',
      '/history',
      '/dashboard'
    ]

    const d = driver({
      showProgress: true,
      allowClose: false,
      nextBtnText: 'ถัดไป',
      prevBtnText: 'ย้อนกลับ',
      doneBtnText: 'เริ่มต้นใช้งาน!',
      progressText: '{{current}} จาก {{total}}',
      onHighlightStarted: (element) => {
        if (element?.popover?.element) {
           setTimeout(() => updateCursorPosition(element.popover.element), 300)
        }
      },
      onNextClick: (element, step, { state }) => {
        const nextIndex = state.activeIndex + 1
        hideCursor()
        if (nextIndex < routesUser.length && router.currentRoute.value.path !== routesUser[nextIndex]) {
          router.push(routesUser[nextIndex]).then(() => {
            setTimeout(() => d.moveNext(), 1200)
          })
        } else {
          d.moveNext()
        }
      },
      onPrevClick: (element, step, { state }) => {
        const prevIndex = state.activeIndex - 1
        hideCursor()
        if (prevIndex >= 0 && router.currentRoute.value.path !== routesUser[prevIndex]) {
          router.push(routesUser[prevIndex]).then(() => {
            setTimeout(() => d.movePrevious(), 1200)
          })
        } else {
          d.movePrevious()
        }
      },
      onDestroyStarted: () => {
        hideCursor()
        isTourActive = false
        localStorage.setItem('tour_completed_user', 'true')
        router.push('/dashboard')
        d.destroy()
      },
      steps: [
        {
          element: 'header',
          popover: {
            title: 'ยินดีต้อนรับสู่ IT Support 👋',
            description: 'นี่คือระบบแจ้งซ่อมและยืมอุปกรณ์ไอที ระบบจะช่วยให้คุณทำงานได้สะดวกขึ้น',
            side: 'bottom',
            align: 'start'
          }
        },
        {
          element: 'form',
          popover: {
            title: 'แจ้งปัญหาการใช้งาน',
            description: 'หากคอมพิวเตอร์มีปัญหา หรือระบบเครือข่ายขัดข้อง สามารถกรอกรายละเอียดและกดแจ้งที่หน้านี้ได้เลยครับ',
            side: 'left',
            align: 'start'
          }
        },
        {
          element: '.grid',
          popover: {
            title: 'ยืม-คืนอุปกรณ์',
            description: 'ต้องการยืมสายชาร์จ, ปลั๊กพ่วง หรืออุปกรณ์อื่นๆ สามารถตรวจสอบสต๊อกและกดขอยืมจากหน้านี้ได้เลยครับ',
            side: 'left',
            align: 'start'
          }
        },
        {
          element: 'table',
          popover: {
            title: 'ประวัติการใช้งาน',
            description: 'คุณสามารถติดตามสถานะการแจ้งซ่อม และดูวันครบกำหนดคืนอุปกรณ์ได้จากตารางประวัติตรงนี้ครับ',
            side: 'left',
            align: 'start'
          }
        },
        {
          element: '#profile-menu-button',
          popover: {
            title: 'ตั้งค่าโปรไฟล์',
            description: 'คลิกที่ปุ่มนี้เพื่อตั้งค่าโปรไฟล์ หรือเปลี่ยนรหัสผ่านได้ตลอดเวลาครับ',
            side: 'bottom',
            align: 'end'
          }
        }
      ]
    })
    
    // Start tour safely by waiting for element
    const checkAndStart = (attempts = 0) => {
      if (document.querySelector('header')) {
        setTimeout(() => d.drive(), 100)
      } else if (attempts < 30) {
        setTimeout(() => checkAndStart(attempts + 1), 100)
      }
    }
    checkAndStart()
  }

  const startAdminTour = async () => {
    if (!import.meta.client) return
    if (localStorage.getItem('tour_completed_admin')) return
    if (isTourActive) return

    isTourActive = true

    injectCursorCSS()
    createCursor()

    const routesAdmin = [
      '/admin',
      '/admin/issues',
      '/admin/inventory',
      '/admin/users'
    ]

    const d = driver({
      showProgress: true,
      allowClose: false,
      nextBtnText: 'ถัดไป',
      prevBtnText: 'ย้อนกลับ',
      doneBtnText: 'พร้อมทำงาน!',
      progressText: '{{current}} จาก {{total}}',
      onHighlightStarted: (element) => {
        if (element?.popover?.element) {
           setTimeout(() => updateCursorPosition(element.popover.element), 300)
        }
      },
      onNextClick: (element, step, { state }) => {
        const nextIndex = state.activeIndex + 1
        hideCursor()
        if (nextIndex < routesAdmin.length && router.currentRoute.value.path !== routesAdmin[nextIndex]) {
          router.push(routesAdmin[nextIndex]).then(() => {
            setTimeout(() => d.moveNext(), 1200)
          })
        } else {
          d.moveNext()
        }
      },
      onPrevClick: (element, step, { state }) => {
        const prevIndex = state.activeIndex - 1
        hideCursor()
        if (prevIndex >= 0 && router.currentRoute.value.path !== routesAdmin[prevIndex]) {
          router.push(routesAdmin[prevIndex]).then(() => {
            setTimeout(() => d.movePrevious(), 1200)
          })
        } else {
          d.movePrevious()
        }
      },
      onDestroyStarted: () => {
        hideCursor()
        isTourActive = false
        localStorage.setItem('tour_completed_admin', 'true')
        router.push('/admin')
        d.destroy()
      },
      steps: [
        {
          element: '#admin-tour-start',
          popover: {
            title: 'ยินดีต้อนรับ Admin 👑',
            description: 'นี่คือหน้า Dashboard สำหรับสรุปสถิติภาพรวมทั้งหมดของระบบครับ',
            side: 'bottom',
            align: 'start'
          }
        },
        {
          element: '#admin-issues-table', 
          popover: {
            title: 'จัดการปัญหา (Issues)',
            description: 'หน้านี้ใช้รับเรื่องแจ้งซ่อม อัปเดตสถานะ และดูพิกัดแผนที่ของผู้ใช้งาน',
            side: 'left',
            align: 'start'
          }
        },
        {
          element: '#admin-inventory-table', 
          popover: {
            title: 'คลังอุปกรณ์',
            description: 'ที่หน้านี้คุณสามารถอนุมัติการขอยืม รับคืนอุปกรณ์ และเช็คอุปกรณ์ที่ใกล้หมดสต๊อก',
            side: 'left',
            align: 'start'
          }
        },
        {
          element: '#admin-users-table', 
          popover: {
            title: 'จัดการผู้ใช้งาน',
            description: 'จัดการแบนผู้ใช้ที่มีพฤติกรรมไม่เหมาะสม หรือตั้งค่าสิทธิ์ต่างๆ ได้จากตารางนี้ครับ',
            side: 'left',
            align: 'start'
          }
        }
      ]
    })
    
    // Start tour safely by waiting for element
    const checkAndStart = (attempts = 0) => {
      if (document.querySelector('#admin-tour-start')) {
        setTimeout(() => d.drive(), 300)
      } else if (attempts < 30) {
        setTimeout(() => checkAndStart(attempts + 1), 100)
      }
    }
    checkAndStart()
  }

  return {
    startUserTour,
    startAdminTour
  }
}
