<template>
  <div class="cia-ferias-flex-table">
    <oxd-text tag="p" class="cia-ferias-flex-table-title">
      {{ titleName }}
    </oxd-text>
    <oxd-divider class="cia-ferias-flex-table-divider" />
    <div
      v-for="(item, index) in items"
      :key="index"
      class="cia-ferias-flex-table-row"
    >
      <oxd-text class="cia-ferias-flex-table-content">{{
        item.label
      }}</oxd-text>
      <oxd-text :class="getClass(item.value.status)">
        {{ item.value.message }}
      </oxd-text>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FlexTable',
  props: {
    titleName: {
      type: String,
      required: true,
    },
    items: {
      type: Array,
      required: true,
      default: () => [],
    },
  },
  methods: {
    getClass(id) {
      if (id === 1) {
        return 'cia-ferias-flex-table-value --success';
      }
      if (id === 2) {
        return 'cia-ferias-flex-table-value --warning';
      }
      return 'cia-ferias-flex-table-value --error';
    },
  },
};
</script>

<style scoped lang="scss">
.cia-ferias-flex-table {
  margin-bottom: 0.75rem;
  &-title {
    font-weight: 700;
    font-size: 16px;
  }
  &-value {
    font-weight: 700;
    &.--success {
      color: $oxd-feedback-success-color;
    }
    &.--warning {
      color: $oxd-feedback-warn-color;
    }
    &.--error {
      color: $oxd-feedback-danger-color;
    }
  }
  &-row {
    display: flex;
    width: 100%;
    padding: 0.2rem 0;
  }
  &-content {
    flex: 1;
  }
  &-divider {
    border-top-color: $oxd-interface-gray-darken-1-color;
  }
}
</style>
