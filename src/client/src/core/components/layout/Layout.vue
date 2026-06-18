<template>
  <oxd-layout
    :class="{
      'orangehrm-upgrade-layout': showUpgrade,
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
import {OxdLayout} from '@ohrm/oxd';
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
    const leaveModuleClass = 'orangehrm-leave-module';
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
      const tabIndex = Array.from(tab.parentElement?.children || []).indexOf(tab);
      if (tabIndex !== 1) return;

      event.preventDefault();
      event.stopImmediatePropagation();
      window.dispatchEvent(new CustomEvent('ca:generate-vacation-planning-pdf'));
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
.orangehrm-upgrade-layout {
  .oxd-topbar-header-userarea {
    align-self: center;
    margin-left: unset;
  }
}

.oxd-main-menu-search,
.oxd-main-menu-search-icon {
  display: none;
}

.orangehrm-pim-employee-list-page
  .oxd-topbar-body-nav-tab:has(.bi-three-dots-vertical) {
  display: none;
}

.orangehrm-leave-module .oxd-topbar-body-nav-tab:nth-of-type(2) {
  .oxd-topbar-body-nav-tab-item {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
  }

  .oxd-topbar-body-nav-tab-item::before {
    width: 1.05rem;
    height: 1.05rem;
    background: url('~@/orangehrmLeavePlugin/assets/pdf.svg') center / contain
      no-repeat;
    content: '';
  }
}

</style>
