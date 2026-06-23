<!--
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */
 -->

<template>
  <div class="cia-ferias-background-container">
    <div class="cia-ferias-card-container">
      <oxd-text tag="h6" class="cia-ferias-main-title">
        {{ $t('help.getting_started_with_cia_ferias') }}
      </oxd-text>
      <oxd-divider />
      <oxd-text tag="p" class="cia-ferias-support-text">
        Central interna de suporte da CIA Férias.
      </oxd-text>
      <br />
      <oxd-text tag="p" class="cia-ferias-support-text">
        Para ajuda operacional, abra um chamado no canal interno definido pela
        sua equipe.
      </oxd-text>
      <div class="cia-ferias-support">
        <oxd-grid :cols="2" class="cia-ferias-support-channel --mail">
          <oxd-grid-item>
            <img :src="helpImgSrc" alt="help image" />
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-text class="cia-ferias-sub-title" tag="p">
              {{ $t('help.customer_support') }}
            </oxd-text>
            <oxd-text tag="p" class="cia-ferias-support-text">
              O suporte interno acompanha dúvidas de acesso, cadastros e fluxos
              de férias.
            </oxd-text>
          </oxd-grid-item>
        </oxd-grid>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      helpImgSrc: `${window.appGlobal.publicPath}/images/help.png`,
    };
  },
};
</script>

<style lang="scss" scoped>
.cia-ferias-support-text {
  font-size: 12px;
}
.cia-ferias-support {
  padding: 1rem 0;
  &-channel {
    grid-template-columns: 50px 1fr;
    align-items: center;
    img {
      width: 40px;
      height: 40px;
    }
  }
  &-link {
    text-decoration: none;
    color: $oxd-primary-one-color;
  }
}
</style>
