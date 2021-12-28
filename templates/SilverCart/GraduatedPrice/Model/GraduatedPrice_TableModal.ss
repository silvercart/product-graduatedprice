<div class="modal fade" id="modal-price-table-{$ID}" tabindex="-1" role="dialog" aria-labelledby="modal-price-table-{$ID}-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-price-table-{$ID}-label">{$Title}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h5><%t SilverCart\GraduatedPrice\Model\GraduatedPrice.BUY_WITH_VOLUME_DISCOUNT 'Buy with quantity discount' %>:</h5>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="text-right w-50"><%t SilverCart\GraduatedPrice\Model\GraduatedPrice.FROM 'from' %></th>
                            <th class="text-right"><%t SilverCart\Model\Product\Product.PRICE_SINGLE 'Price single' %></th>
                        </tr>
                    </thead>
                    <tbody>
                    <% loop $getGraduatedPricesForCustomersGroups %>
                        <tr>
                        <% if $minimumQuantity == 0 %>
                            <td class="text-right">1</td>
                        <% else %>
                            <td class="text-right">{$minimumQuantity}</td>
                        <% end_if %>
                            <td class="text-right">{$price.Nice}</td>
                        </tr>
                    <% end_loop %>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>