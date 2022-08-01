<% if $getGraduatedPricesForCustomersGroups.count > 1 || $getGraduatedPricesForCustomersGroups.first.minimumQuantity > 1 %>
    <div class="silvercart-graduated-price-table">
        <h3><%t SilverCart\GraduatedPrice\Model\GraduatedPrice.BUY_WITH_VOLUME_DISCOUNT 'Buy with quantity discount' %>:</h3>
        <table class="table font-weight-bold">
            <thead>
                <tr>
                    <th class="text-right"></th>
                    <th class="text-right w-10"><%t SilverCart\GraduatedPrice\Model\GraduatedPrice.FROM 'from' %></th>
                    <th class="text-right"><%t SilverCart\Model\Product\Product.PRICE_SINGLE 'Price single' %></th>
                </tr>
            </thead>
            <tbody>
            <% loop $getGraduatedPricesForCustomersGroups %>
                <% if $isTopseller %>
                <tr class="badge-secondary">
                    <td class="text-right">{$fieldLabel('isTopseller')}</td>
                 <% else %>
                <tr>
                    <td class="text-right"></td>
                <% end_if %>
                <% if $minimumQuantity == 0 %>
                    <td class="text-right">1</td>
                <% else %>
                    <td class="text-right">
                        {$minimumQuantity}
                    </td>
                <% end_if %>
                    <td class="text-right">{$price.Nice}</td>
                </tr>
            <% end_loop %>
            </tbody>
        </table>
    </div>
<% end_if %>
