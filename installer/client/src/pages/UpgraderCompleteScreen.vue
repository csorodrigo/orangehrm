<template>
  <div class="cia-ferias-installer-page">
    <oxd-text tag="h5" class="cia-ferias-installer-page-title">
      Upgrade Complete
    </oxd-text>
    <br />
    <oxd-text tag="p" class="cia-ferias-installer-page-content">
      You have successfully upgraded to CIA Férias version
      {{ productversion }}.
    </oxd-text>
    <br />
    <br />
    <oxd-button
      class="cia-ferias-upgrader-complete-button"
      display-type="secondary"
      label="Launch CIA Férias"
      @click="launch"
    />
  </div>
</template>

<script>
import {navigate} from '@/core/util/helper/navigation.ts';
export default {
  name: 'UpgraderCompleteScreen',
  props: {
    productversion: {
      type: String,
      required: true,
    },
  },
  methods: {
    launch() {
      navigate('/');
    },
  },
};
</script>

<style src="./installer-page.scss" lang="scss" scoped></style>
<style lang="scss" scoped>
.cia-ferias-upgrader-complete {
  padding: 0 1rem;
  color: $oxd-interface-gray-darken-1-color;
}
.cia-ferias-upgrader-complete-button {
  margin-right: auto;
}
</style>
