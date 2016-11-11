<% require css(silvercart_product_graduatedprice/css/SilvercartGraduatedPriceFront.css) %>
<% if getGraduatedPricesForCustomersGroups %>
    <div class="silvercart-graduated-price-table">
        <div>
            <h3><% _t('SilvercartGraduatedPrice.BUY_WITH_VOLUME_DISCOUNT') %>:</h3>
            <table>
                <% loop getGraduatedPricesForCustomersGroups %>
                    <tr>
                        <td class="right">$minimumQuantity</td>
                        <td class="right">$price.Nice</td>
                    </tr>
                <% end_loop %>
            </table>
        </div>
    </div>
<% end_if %>