
<% if getGraduatedPricesForCustomersGroups %>
    <div class="silvercart-graduated-price-table">
        <div>
            <h3><% _t('SilvercartGraduatedPrice.BUY_WITH_VOLUME_DISCOUNT') %>:</h3>
            <table>
                <% control getGraduatedPricesForCustomersGroups %>
                    <tr>
                        <td class="right">$minimumQuantity</td>
                        <td class="right">$price.Nice</td>
                    </tr>
                <% end_control %>
            </table>
        </div>
    </div>
<% end_if %>