<template>
  <oxd-layout
    :class="{
      'cia-ferias-upgrade-layout': showUpgrade,
    }"
    v-bind="layoutAttrs"
  >
    <template v-for="(_, name) in $slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData" />
    </template>
    <template v-if="showUpgrade" #topbar-header-right-area>
      <upgrade-button v-if="showUpgrade" />
    </template>
    <template #user-actions>
      <li>
        <a
          href="#"
          role="menuitem"
          class="oxd-userdropdown-link"
          @click="openAboutModel"
        >
          {{ $t('general.about') }}
        </a>
      </li>
      <li>
        <a :href="supportUrl" role="menuitem" class="oxd-userdropdown-link">
          {{ $t('general.support') }}
        </a>
      </li>
      <li>
        <a :href="myDetailsUrl" role="menuitem" class="oxd-userdropdown-link">
          Meus Dados
        </a>
      </li>
      <li v-if="updatePasswordUrl">
        <a
          :href="updatePasswordUrl"
          role="menuitem"
          class="oxd-userdropdown-link"
        >
          {{ $t('general.change_password') }}
        </a>
      </li>
      <li>
        <a :href="logoutUrl" role="menuitem" class="oxd-userdropdown-link">
          {{ $t('general.logout') }}
        </a>
      </li>
    </template>
    <template #nav-actions>
      <oxd-icon-button
        name="question-lg"
        :title="$t('general.help')"
        @click="onClickSupport"
      />
    </template>
  </oxd-layout>
  <about v-if="showAboutModel" @close="closeAboutModel"></about>
</template>

<script>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  provide,
  readonly,
  ref,
  useAttrs,
} from 'vue';
import About from '@/core/pages/About.vue';
import {OxdLayout} from '@cia-ferias/oxd';
import {dateFormatKey} from '@/core/util/composable/useDateFormat';
import UpgradeButton from '@/core/components/buttons/UpgradeButton.vue';

export default {
  components: {
    about: About,
    'oxd-layout': OxdLayout,
    'upgrade-button': UpgradeButton,
  },
  inheritAttrs: false,
  props: {
    permissions: {
      type: Object,
      default: () => ({}),
    },
    logoutUrl: {
      type: String,
      default: '#',
    },
    supportUrl: {
      type: String,
      default: '#',
    },
    updatePasswordUrl: {
      type: String,
      default: '#',
    },
    dateFormat: {
      type: Object,
      default: null,
    },
    helpUrl: {
      type: String,
      default: null,
    },
    showUpgrade: {
      type: Boolean,
      default: false,
    },
  },
  setup(props) {
    const attrs = useAttrs();
    const showAboutModel = ref(false);
    const myDetailsUrl = `${window.appGlobal.baseUrl}/pim/viewMyDetails`;
    const leaveModuleClass = 'cia-ferias-leave-module';
    provide('permissions', readonly(props.permissions));
    provide(dateFormatKey, readonly(props.dateFormat));

    const openAboutModel = () => {
      showAboutModel.value = true;
    };

    const closeAboutModel = () => {
      showAboutModel.value = false;
    };

    const onClickSupport = () => {
      if (props.helpUrl) window.open(props.helpUrl, '_blank');
    };

    const syncModuleBodyClass = () => {
      document.body.classList.toggle(
        leaveModuleClass,
        window.location.pathname.includes('/leave/'),
      );
    };

    const handleVacationPdfClick = (event) => {
      const tab = event.target.closest?.('.oxd-topbar-body-nav-tab');
      if (!tab || !isLeaveModule()) return;
      const tabIndex = Array.from(tab.parentElement?.children || []).indexOf(
        tab,
      );
      if (tabIndex !== 1) return;

      event.preventDefault();
      event.stopImmediatePropagation();
      window.dispatchEvent(
        new CustomEvent('ca:generate-vacation-planning-pdf'),
      );
    };

    const getTopbarMenuItems = () =>
      attrs.topbarMenuItems ?? attrs['topbar-menu-items'];

    const isLeaveModule = () => window.location.pathname.includes('/leave/');

    const layoutAttrs = computed(() => {
      const topbarMenuItems = getTopbarMenuItems();

      if (!isLeaveModule() || !Array.isArray(topbarMenuItems)) {
        return attrs;
      }

      const planningItem = {
        name: 'Planejamento',
        url: `${window.appGlobal.baseUrl}/leave/viewLeaveList`,
        active: window.location.pathname.includes('/leave/viewLeaveList'),
        children: [],
      };
      const pdfItem = {
        name: '',
        url: '#',
        active: false,
        children: [],
      };

      return {
        ...attrs,
        topbarMenuItems: [planningItem, pdfItem],
      };
    });

    onMounted(() => {
      syncModuleBodyClass();
      document.addEventListener('click', handleVacationPdfClick, true);
    });
    onBeforeUnmount(() => {
      document.removeEventListener('click', handleVacationPdfClick, true);
      document.body.classList.remove(leaveModuleClass);
    });

    return {
      onClickSupport,
      showAboutModel,
      layoutAttrs,
      myDetailsUrl,
      openAboutModel,
      closeAboutModel,
    };
  },
};
</script>

<style lang="scss">
body {
  background-color: #f3f6fb;
}

.oxd-layout {
  background-color: #f3f6fb;
  min-height: 100vh;
}

.oxd-layout-navigation {
  .oxd-sidepanel {
    margin: 0;
    min-height: 100vh;
    border-radius: 0 1.25rem 1.25rem 0;
    box-shadow: 0 24px 60px rgba(26, 55, 104, 0.12);
    overflow: visible;
  }

  .oxd-main-menu-button {
    right: 0.75rem;
    z-index: 5;
  }

  .oxd-main-menu-item {
    border-radius: 999px;
    margin: 0.18rem 0.7rem;
    transition: background-color 160ms ease, color 160ms ease,
      transform 160ms ease;
  }

  .oxd-main-menu-item:hover {
    background-color: rgba(27, 76, 178, 0.08);
    transform: translateX(2px);
  }

  .oxd-main-menu-item.active {
    background: linear-gradient(135deg, #005eea, #273381);
    box-shadow: 0 14px 28px rgba(39, 51, 129, 0.24);
  }
}

.oxd-topbar {
  margin: 0;
  border-radius: 0 0 0 1.15rem;
  overflow: visible;
  box-shadow: 0 18px 42px rgba(26, 55, 104, 0.1);
}

.oxd-topbar-header {
  background: linear-gradient(135deg, #005eea, #273381);
}

.oxd-topbar-body {
  background-color: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(10px);
  border-radius: 0 0 0 1.15rem;
  overflow: visible;
}

.oxd-topbar-body-nav-slot {
  padding-right: 0.9rem;
}

.oxd-layout-context {
  padding-top: 1.25rem;
}

.oxd-sheet,
.cia-ferias-paper-container,
.oxd-table-filter {
  border-radius: 1.15rem;
  box-shadow: 0 18px 46px rgba(26, 55, 104, 0.08);
}

.oxd-button,
.oxd-topbar-body-nav-tab-item {
  transition: transform 160ms ease, box-shadow 160ms ease,
    background-color 160ms ease;
}

.oxd-button:hover,
.oxd-topbar-body-nav-tab-item:hover {
  transform: translateY(-1px);
}

.cia-ferias-upgrade-layout {
  .oxd-topbar-header-userarea {
    align-self: center;
    margin-left: unset;
  }
}

.oxd-main-menu-search,
.oxd-main-menu-search-icon {
  display: none;
}

.cia-ferias-pim-employee-list-page
  .oxd-topbar-body-nav-tab:has(.bi-three-dots-vertical) {
  display: none;
}

.cia-ferias-leave-module .oxd-topbar-body-nav-tab:nth-of-type(2) {
  .oxd-topbar-body-nav-tab-item {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
  }

  .oxd-topbar-body-nav-tab-item::before {
    width: 1.05rem;
    height: 1.05rem;
    background: url('~@/ciaFeriasLeavePlugin/assets/pdf.svg') center / contain
      no-repeat;
    content: '';
  }
}
</style>
