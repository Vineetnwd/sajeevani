import React, { useEffect, useState } from 'react';
import { StyleSheet, View, Text, ScrollView, ActivityIndicator, TouchableOpacity, SafeAreaView, StatusBar, Linking } from 'react-native';
import { useLocalSearchParams, router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';

const API_BASE = 'https://praanveda.net/web/api/mr.php';

export default function ReceiptScreen() {
  const { orderId } = useLocalSearchParams();
  const [loading, setLoading] = useState(true);
  const [data, setData] = useState<any>(null);

  useEffect(() => {
    if (orderId) {
      fetch(`${API_BASE}?action=get_order_receipt&order_id=${orderId}`)
        .then(res => res.json())
        .then(res => {
          if (res.status === 'success') setData(res.data);
          setLoading(false);
        })
        .catch(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, [orderId]);

  const generatePDF = async () => {
    if (!data) return;
    try {
      const html = `
        <html>
          <head>
            <style>
              body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 40px; color: #333; }
              .header { text-align: center; border-bottom: 2px solid #059669; padding-bottom: 20px; margin-bottom: 30px; }
              .logo { font-size: 28px; font-weight: bold; color: #059669; }
              .subtitle { font-size: 14px; color: #666; margin-top: 5px; }
              .row { display: flex; justify-content: space-between; margin-bottom: 30px; }
              .col { flex: 1; }
              .label { font-size: 12px; color: #888; text-transform: uppercase; font-weight: bold; margin-bottom: 4px; }
              .val { font-size: 14px; font-weight: bold; margin-bottom: 15px; }
              table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
              th, td { padding: 12px 0; border-bottom: 1px solid #eee; text-align: left; }
              th { font-size: 12px; color: #888; text-transform: uppercase; }
              .total-row { border-top: 2px solid #333; font-weight: bold; font-size: 18px; }
              .stamp { display: inline-block; padding: 5px 15px; border: 2px solid #059669; color: #059669; font-size: 20px; font-weight: bold; text-transform: uppercase; border-radius: 5px; transform: rotate(-10deg); margin-top: 20px; }
            </style>
          </head>
          <body>
            <div class="header">
              <div class="logo">Sanjeevni Receipt</div>
              <div class="subtitle">Official Payment Record</div>
            </div>
            <div class="row">
              <div class="col">
                <div class="label">Billed To</div>
                <div class="val">Dr. ${data.order.doctor_name.replace(/^Dr\.\s*/i, '')}</div>
                <div class="val" style="font-weight: normal; font-size: 13px;">${data.order.clinic_name || 'Clinic'}<br/>${data.order.clinic_address || ''}</div>
              </div>
              <div class="col" style="text-align: right;">
                <div class="label">Order / Receipt No.</div>
                <div class="val">#DO-${data.order.id}</div>
                <div class="label">Date</div>
                <div class="val">${new Date(data.order.created_at).toLocaleDateString()}</div>
                <div class="label">Payment Method</div>
                <div class="val">${data.order.payment_method || 'N/A'}</div>
              </div>
            </div>
            <table>
              <thead>
                <tr>
                  <th>Item Description</th>
                  <th style="text-align: center;">Qty</th>
                  <th style="text-align: right;">Price</th>
                  <th style="text-align: right;">Amount</th>
                </tr>
              </thead>
              <tbody>
                ${data.items.map((item: any) => `
                  <tr>
                    <td>${item.product_name}</td>
                    <td style="text-align: center;">${item.quantity}</td>
                    <td style="text-align: right;">₹${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td style="text-align: right;">₹${parseFloat(item.line_total).toFixed(2)}</td>
                  </tr>
                `).join('')}
                <tr class="total-row">
                  <td colspan="3" style="text-align: right; padding-top: 20px;">Total Amount Paid</td>
                  <td style="text-align: right; padding-top: 20px;">₹${parseFloat(data.order.total_amount).toFixed(2)}</td>
                </tr>
              </tbody>
            </table>
            <div style="text-align: right;">
              <div class="stamp">PAID IN FULL</div>
            </div>
          </body>
        </html>
      `;
      const { uri } = await Print.printToFileAsync({ html });
      await Sharing.shareAsync(uri);
    } catch (e) {
      console.log(e);
    }
  };

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#059669" />
      </View>
    );
  }

  if (!data || !data.order) {
    return (
      <SafeAreaView style={styles.center}>
        <Text style={styles.errorText}>Receipt not found or unavailable.</Text>
        <TouchableOpacity style={styles.backBtn} onPress={() => router.back()}>
          <Text style={styles.backBtnText}>Go Back</Text>
        </TouchableOpacity>
      </SafeAreaView>
    );
  }

  const { order, items } = data;

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#F8FAFC" />
      
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backIcon} onPress={() => router.back()}>
          <Ionicons name="arrow-back" size={24} color="#0F172A" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Payment Receipt</Text>
        <TouchableOpacity onPress={generatePDF}>
          <Ionicons name="share-outline" size={24} color="#059669" />
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.receiptCard}>
          {/* Receipt Top */}
          <View style={styles.receiptTop}>
            <View style={styles.paidBadge}>
              <Ionicons name="checkmark-circle" size={16} color="#059669" />
              <Text style={styles.paidText}>PAID</Text>
            </View>
            <Text style={styles.amountText}>₹{parseFloat(order.total_amount).toFixed(2)}</Text>
            <Text style={styles.dateText}>{new Date(order.created_at).toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</Text>
          </View>

          <View style={styles.divider} />

          {/* Details */}
          <View style={styles.detailsSection}>
            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>Receipt No.</Text>
              <Text style={styles.detailVal}>#DO-{order.id}</Text>
            </View>
            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>Payment Method</Text>
              <Text style={styles.detailVal}>{order.payment_method || 'N/A'}</Text>
            </View>
            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>Doctor</Text>
              <Text style={styles.detailVal}>Dr. {order.doctor_name.replace(/^Dr\.\s*/i, '')}</Text>
            </View>
            {order.clinic_name && (
              <View style={styles.detailRow}>
                <Text style={styles.detailLabel}>Clinic</Text>
                <Text style={styles.detailVal}>{order.clinic_name}</Text>
              </View>
            )}
          </View>

          <View style={styles.divider} />

          {/* Items */}
          <View style={styles.itemsSection}>
            <Text style={styles.sectionTitle}>Items Breakdown</Text>
            {items.map((item: any, idx: number) => (
              <View key={idx} style={styles.itemRow}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.itemName}>{item.product_name}</Text>
                  <Text style={styles.itemQty}>{item.quantity} × ₹{parseFloat(item.unit_price).toFixed(2)}</Text>
                </View>
                <Text style={styles.itemTotal}>₹{parseFloat(item.line_total).toFixed(2)}</Text>
              </View>
            ))}
          </View>

          <View style={styles.receiptFooter}>
            <Text style={styles.footerTotalLabel}>Total Paid</Text>
            <Text style={styles.footerTotalVal}>₹{parseFloat(order.total_amount).toFixed(2)}</Text>
          </View>
        </View>

        <TouchableOpacity style={styles.downloadBtn} onPress={generatePDF}>
          <Ionicons name="download-outline" size={20} color="#fff" />
          <Text style={styles.downloadBtnText}>Download PDF</Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F8FAFC' },
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  errorText: { fontSize: 16, color: '#64748B', marginBottom: 20 },
  backBtn: { backgroundColor: '#0F172A', paddingHorizontal: 20, paddingVertical: 10, borderRadius: 8 },
  backBtnText: { color: '#fff', fontWeight: 'bold' },
  
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, paddingTop: 10, paddingBottom: 15, backgroundColor: '#F8FAFC' },
  backIcon: { width: 40, height: 40, justifyContent: 'center', alignItems: 'flex-start' },
  headerTitle: { fontSize: 18, fontWeight: '800', color: '#0F172A' },
  
  scroll: { padding: 20, paddingBottom: 40 },
  
  receiptCard: { backgroundColor: '#FFFFFF', borderRadius: 20, padding: 24, elevation: 4, shadowColor: '#000', shadowOpacity: 0.08, shadowRadius: 10, shadowOffset: { width: 0, height: 4 } },
  
  receiptTop: { alignItems: 'center', marginBottom: 20 },
  paidBadge: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#D1FAE5', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 20, marginBottom: 12 },
  paidText: { color: '#047857', fontWeight: '800', fontSize: 12, marginLeft: 6, letterSpacing: 1 },
  amountText: { fontSize: 36, fontWeight: '900', color: '#0F172A', letterSpacing: -1 },
  dateText: { fontSize: 13, color: '#64748B', marginTop: 4, fontWeight: '500' },
  
  divider: { height: 1, backgroundColor: '#F1F5F9', marginVertical: 20 },
  
  detailsSection: { spaceY: 10 },
  detailRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 12 },
  detailLabel: { fontSize: 13, color: '#64748B', fontWeight: '500' },
  detailVal: { fontSize: 14, color: '#0F172A', fontWeight: '700' },
  
  itemsSection: {},
  sectionTitle: { fontSize: 14, fontWeight: '800', color: '#0F172A', marginBottom: 16, textTransform: 'uppercase', letterSpacing: 0.5 },
  itemRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  itemName: { fontSize: 14, fontWeight: '700', color: '#1E293B', marginBottom: 2 },
  itemQty: { fontSize: 12, color: '#64748B' },
  itemTotal: { fontSize: 15, fontWeight: '800', color: '#0F172A' },
  
  receiptFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 10, paddingTop: 20, borderTopWidth: 2, borderTopColor: '#F1F5F9' },
  footerTotalLabel: { fontSize: 16, fontWeight: '800', color: '#0F172A' },
  footerTotalVal: { fontSize: 20, fontWeight: '900', color: '#059669' },
  
  downloadBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: '#0F172A', paddingVertical: 16, borderRadius: 16, marginTop: 20 },
  downloadBtnText: { color: '#fff', fontSize: 16, fontWeight: '700', marginLeft: 8 },
});
