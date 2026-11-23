import { InfoPage } from '@/components/shop/info-page';

export default function Terms() {
    return (
        <InfoPage
            title="Terms of sale"
            description="The terms that apply when you buy from Loom & Lane."
            sections={[
                {
                    heading: 'Orders',
                    body: <p>Your order is confirmed once payment has been taken and you receive an order reference.</p>,
                },
                {
                    heading: 'Prices',
                    body: <p>All prices are shown in pounds sterling (GBP) and include VAT. Delivery charges are shown before you pay.</p>,
                },
                {
                    heading: 'Cancellations & returns',
                    body: <p>You have the right to cancel your order within 14 days of delivery. Our 30-day returns policy goes beyond this.</p>,
                },
            ]}
            footnote="Sample terms: have these reviewed by a legal professional before launch."
        />
    );
}
