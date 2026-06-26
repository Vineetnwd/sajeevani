import React, { useState, useEffect, useCallback } from 'react';
import {
  StyleSheet, View, Text, FlatList, TouchableOpacity,
  ActivityIndicator, RefreshControl, StatusBar, Modal,
  ScrollView, Platform, useColorScheme
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from 'expo-router';

const API_BASE = 'https://praanveda.net/web/api/mr.php';

const getStatusConfig = (isDark: boolean): Record<string, { bg: string; text: string; dot: string; label: string; border: string }> => ({
  Pending: { bg: isDark ? '#78350F' : '#FFFBEB', text: isDark ? '#FDE68A' : '#92400E', dot: '#F59E0B', label: 'Pending', border: isDark ? '#92400E' : '#FDE68A' },
  Confirmed: { bg: isDark ? '#1E3A8A' : '#EFF6FF', text: isDark ? '#BFDBFE' : '#1E40AF', dot: '#3B82F6', label: 'Confirmed', border: isDark ? '#1E40AF' : '#BFDBFE' },
  Dispatched: { bg: isDark ? '#4C1D95' : '#F5F3FF', text: isDark ? '#DDD6FE' : '#5B21B6', dot: '#8B5CF6', label: 'Dispatched', border: isDark ? '#5B21B6' : '#DDD6FE' },
  Delivered: { bg: isDark ? '#064E3B' : '#F0FDF4', text: isDark ? '#A7F3D0' : '#166534', dot: '#22C55E', label: 'Delivered', border: isDark ? '#065F46' : '#BBF7D0' },
  Cancelled: { bg: isDark ? '#7F1D1D' : '#FEF2F2', text: isDark ? '#FECACA' : '#991B1B', dot: '#EF4444', label: 'Cancelled', border: isDark ? '#991B1B' : '#FECACA' },
});

type Order = {
  id: number; status: string; notes: string;
  total_amount: string; created_at: string;
  status_remarks: string | null; courier_company: string | null; awb_no: string | null;
  doctor_name: string; doctor_phone: string; item_count: number;
};
type OrderItem = { product_name: string; quantity: number; unit_price: string; line_total: string };

const FILTERS = ['All', 'Pending', 'Confirmed', 'Dispatched', 'Delivered', 'Cancelled'];

export default function MyOrdersScreen() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const styles = getStyles(isDark);
  const statusConfig = getStatusConfig(isDark);

  const [mrId, setMrId] = useState('');
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeFilter, setActiveFilter] = useState('All');
  const [detailOrder, setDetailOrder] = useState<Order | null>(null);
  const [detailItems, setDetailItems] = useState<OrderItem[]>([]);
  const [detailLoading, setDetailLoading] = useState(false);

  const fetchOrders = useCallback(async (uid: string) => {
    if (!uid) { setLoading(false); return; }
    try {
      const res = await fetch(`${API_BASE}?action=my_orders&mr_id=${uid}&t=${Date.now()}`);
      const result = await res.json();
      if (result.status === 'success') setOrders(result.data || []);
    } catch (e) { console.error(e); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useFocusEffect(
    useCallback(() => {
      AsyncStorage.getItem('userId').then(id => {
        const uid = id || '';
        setMrId(uid);
        fetchOrders(uid);
      });
    }, [fetchOrders])
  );

  const openDetail = async (order: Order) => {
    setDetailOrder(order);
    setDetailItems([]);
    setDetailLoading(true);
    try {
      const res = await fetch(`${API_BASE}?action=get_order_detail&order_id=${order.id}&t=${Date.now()}`);
      const result = await res.json();
      if (result.status === 'success') setDetailItems(result.data || []);
    } catch (e) { }
    finally { setDetailLoading(false); }
  };

  const filteredOrders = activeFilter === 'All'
    ? orders
    : orders.filter(o => o.status === activeFilter);

  // ─── Stats ────────────────────────────────────────────────────────────
  const totalRevenue = orders.reduce((s, o) => s + parseFloat(o.total_amount || '0'), 0);
  const pendingCount = orders.filter(o => o.status === 'Pending').length;

  // ─── Card ─────────────────────────────────────────────────────────────
  const renderOrder = ({ item, index }: { item: Order; index: number }) => {
    const sc = statusConfig[item.status] || statusConfig['Pending'];
    const date = new Date(item.created_at);
    return (
      <TouchableOpacity
        style={[styles.card, index === 0 && { marginTop: 4 }]}
        onPress={() => openDetail(item)}
        activeOpacity={0.75}
      >
        {/* Top row */}
        <View style={styles.cardTop}>
          <View style={styles.cardIdBlock}>
            <Text style={styles.cardId}>#DO-{item.id}</Text>
            <Text style={styles.cardDate}>
              {date.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: '2-digit' })}
            </Text>
          </View>
          <View style={[styles.statusPill, { backgroundColor: sc.bg, borderColor: sc.border }]}>
            <View style={[styles.statusDot, { backgroundColor: sc.dot }]} />
            <Text style={[styles.statusLabel, { color: sc.text }]}>{sc.label}</Text>
          </View>
        </View>

        {/* Doctor */}
        <View style={styles.doctorRow}>
          <View style={styles.doctorAvatar}>
            <Text style={styles.doctorAvatarText}>{item.doctor_name.charAt(0).toUpperCase()}</Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.doctorName}>Dr. {item.doctor_name}</Text>
            <Text style={styles.doctorPhone}>{item.doctor_phone}</Text>
          </View>
          <View style={styles.amountBlock}>
            <Text style={styles.cardAmount}>₹{parseFloat(item.total_amount).toFixed(0)}</Text>
            <Text style={styles.cardItems}>{item.item_count} item{item.item_count !== 1 ? 's' : ''}</Text>
          </View>
        </View>

        {/* Footer */}
        <View style={styles.cardFooter}>
          <Ionicons name="time-outline" size={12} color="#9CA3AF" />
          <Text style={styles.cardFooterText}>
            {date.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' })}
          </Text>
          {item.notes ? (
            <>
              <View style={styles.dot} />
              <Ionicons name="document-text-outline" size={12} color="#9CA3AF" />
              <Text style={styles.cardFooterText} numberOfLines={1}>{item.notes}</Text>
            </>
          ) : null}
          <View style={{ flex: 1 }} />
          <Text style={styles.tapHint}>View details →</Text>
        </View>
      </TouchableOpacity>
    );
  };

  // ─── Loading ──────────────────────────────────────────────────────────
  if (loading) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <StatusBar barStyle={isDark ? "light-content" : "dark-content"} backgroundColor={isDark ? "#111827" : "#F5F4FF"} />
        <View style={styles.loaderContainer}>
          <ActivityIndicator size="large" color="#6366F1" />
          <Text style={styles.loaderText}>Loading your orders…</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <View style={{ flex: 1, backgroundColor: isDark ? '#022C22' : '#064E3B' }}>
      <SafeAreaView style={{ flex: 1, backgroundColor: isDark ? '#111827' : '#F0FDF4' }} edges={['top']}>
        <StatusBar barStyle="light-content" backgroundColor={isDark ? '#022C22' : '#064E3B'} />

        {/* ── Header ── */}
        <View style={styles.headerWrapper}>
          <View style={styles.header}>
            <View>
              <Text style={styles.pageTitle}>My Orders</Text>
              <Text style={styles.pageSubtitle}>{orders.length} orders submitted</Text>
            </View>
            <View style={styles.headerStats}>
              <View style={styles.headerStatBadge}>
                <Text style={styles.headerStatNum}>{pendingCount}</Text>
                <Text style={styles.headerStatLabel}>Pending</Text>
              </View>
              <View style={[styles.headerStatBadge, { backgroundColor: '#10B981' }]}>
                <Text style={[styles.headerStatNum, { color: '#ECFDF5' }]}>
                  ₹{totalRevenue >= 1000 ? `${(totalRevenue / 1000).toFixed(1)}k` : totalRevenue.toFixed(0)}
                </Text>
                <Text style={[styles.headerStatLabel, { color: '#ECFDF5' }]}>Revenue</Text>
              </View>
            </View>
          </View>
        </View>

      {/* ── Filter Chips ── */}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.filterRow}
        style={styles.filterScroll}
      >
        {FILTERS.map(f => {
          const isActive = activeFilter === f;
          const cnt = f === 'All' ? orders.length : orders.filter(o => o.status === f).length;
          return (
            <TouchableOpacity
              key={f}
              style={[styles.filterChip, isActive && styles.filterChipActive]}
              onPress={() => setActiveFilter(f)}
            >
              <Text style={[styles.filterChipText, isActive && styles.filterChipTextActive]}>{f}</Text>
              {cnt > 0 && (
                <View style={[styles.filterBadge, isActive && styles.filterBadgeActive]}>
                  <Text style={[styles.filterBadgeText, isActive && styles.filterBadgeTextActive]}>{cnt}</Text>
                </View>
              )}
            </TouchableOpacity>
          );
        })}
      </ScrollView>

      {/* ── List / Empty ── */}
      {filteredOrders.length === 0 ? (
        <View style={styles.emptyContainer}>
          <View style={styles.emptyIconWrap}>
            <Ionicons name="receipt-outline" size={40} color="#A5B4FC" />
          </View>
          <Text style={styles.emptyTitle}>No orders found</Text>
          <Text style={styles.emptySubtitle}>
            {activeFilter === 'All'
              ? 'Place your first order from the New Order tab.'
              : `No ${activeFilter} orders yet.`}
          </Text>
        </View>
      ) : (
        <FlatList
          data={filteredOrders}
          keyExtractor={item => item.id.toString()}
          renderItem={renderOrder}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => { setRefreshing(true); fetchOrders(mrId); }}
              colors={['#6366F1']}
              tintColor="#6366F1"
            />
          }
        />
      )}

      {/* ── Detail Bottom Sheet ── */}
      <Modal visible={!!detailOrder} animationType="slide" transparent statusBarTranslucent>
        <View style={styles.overlay}>
          <TouchableOpacity style={styles.overlayBg} onPress={() => setDetailOrder(null)} />
          <View style={styles.sheet}>
            <View style={styles.sheetHandle} />

            {/* Sheet Header */}
            <View style={styles.sheetHeader}>
              <View style={{ flex: 1 }}>
                <Text style={styles.sheetTitle}>Order #DO-{detailOrder?.id}</Text>
                <Text style={styles.sheetSubtitle}>Dr. {detailOrder?.doctor_name}</Text>
              </View>
              {detailOrder && (() => {
                const sc = statusConfig[detailOrder.status] || statusConfig['Pending'];
                return (
                  <View style={[styles.statusPill, { backgroundColor: sc.bg, borderColor: sc.border }]}>
                    <View style={[styles.statusDot, { backgroundColor: sc.dot }]} />
                    <Text style={[styles.statusLabel, { color: sc.text }]}>{sc.label}</Text>
                  </View>
                );
              })()}
              <TouchableOpacity onPress={() => setDetailOrder(null)} style={styles.closeBtn}>
                <Ionicons name="close" size={20} color="#6B7280" />
              </TouchableOpacity>
            </View>

            {/* Meta row */}
            <View style={styles.sheetMeta}>
              <Ionicons name="calendar-outline" size={14} color="#9CA3AF" />
              <Text style={styles.sheetMetaText}>
                {detailOrder ? new Date(detailOrder.created_at).toLocaleString('en-IN') : ''}
              </Text>
            </View>

            {/* Items */}
            <Text style={styles.sheetSectionLabel}>Items Ordered</Text>
            {detailLoading ? (
              <ActivityIndicator color="#6366F1" style={{ marginVertical: 24 }} />
            ) : detailItems.length === 0 ? (
              <Text style={styles.noItemsText}>No items found.</Text>
            ) : (
              <ScrollView style={styles.itemsList} showsVerticalScrollIndicator={false}>
                {detailItems.map((item, i) => (
                  <View key={i} style={[styles.itemRow, i === detailItems.length - 1 && { borderBottomWidth: 0 }]}>
                    <View style={styles.itemIndex}>
                      <Text style={styles.itemIndexText}>{i + 1}</Text>
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.itemName}>{item.product_name}</Text>
                      <Text style={styles.itemMeta}>Qty {item.quantity} × ₹{parseFloat(item.unit_price).toFixed(2)}</Text>
                    </View>
                    <Text style={styles.itemTotal}>₹{parseFloat(item.line_total).toFixed(2)}</Text>
                  </View>
                ))}
              </ScrollView>
            )}

            {/* Total */}
            <View style={styles.sheetTotalRow}>
              <Text style={styles.sheetTotalLabel}>Total Amount</Text>
              <Text style={styles.sheetTotalAmount}>
                ₹{parseFloat(detailOrder?.total_amount || '0').toFixed(2)}
              </Text>
            </View>

            {detailOrder?.status_remarks ? (
              <View style={[styles.sheetNotesRow, { backgroundColor: isDark ? '#064E3B' : '#F0FDF4' }]}>
                <Ionicons name="chatbubble-ellipses-outline" size={14} color={isDark ? '#6EE7B7' : '#166534'} style={{ marginRight: 8 }} />
                <Text style={[styles.sheetNotesText, { color: isDark ? '#6EE7B7' : '#166534' }]}>Admin Remark: {detailOrder.status_remarks}</Text>
              </View>
            ) : null}

            {detailOrder?.status === 'Dispatched' && (detailOrder?.courier_company || detailOrder?.awb_no) ? (
              <View style={[styles.sheetNotesRow, { backgroundColor: isDark ? '#1E3A8A' : '#EFF6FF', flexDirection: 'column', alignItems: 'flex-start' }]}>
                {detailOrder?.courier_company ? (
                  <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 4 }}>
                    <Ionicons name="car-outline" size={14} color={isDark ? '#BFDBFE' : '#1E40AF'} style={{ marginRight: 8 }} />
                    <Text style={[styles.sheetNotesText, { color: isDark ? '#BFDBFE' : '#1E40AF', fontWeight: '700' }]}>Courier: {detailOrder.courier_company}</Text>
                  </View>
                ) : null}
                {detailOrder?.awb_no ? (
                  <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                    <Ionicons name="barcode-outline" size={14} color={isDark ? '#BFDBFE' : '#1E40AF'} style={{ marginRight: 8 }} />
                    <Text style={[styles.sheetNotesText, { color: isDark ? '#BFDBFE' : '#1E40AF', fontWeight: '700' }]}>AWB No: {detailOrder.awb_no}</Text>
                  </View>
                ) : null}
              </View>
            ) : null}

            {detailOrder?.notes ? (
              <View style={styles.sheetNotesRow}>
                <Ionicons name="document-text-outline" size={14} color="#6366F1" style={{ marginRight: 8 }} />
                <Text style={styles.sheetNotesText}>Your Notes: {detailOrder.notes}</Text>
              </View>
            ) : null}
          </View>
        </View>
      </Modal>
      </SafeAreaView>
    </View>
  );
}

const getStyles = (isDark: boolean) => StyleSheet.create({
  container: { flex: 1, backgroundColor: isDark ? '#111827' : '#F0FDF4' },
  loaderContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  loaderText: { marginTop: 12, fontSize: 14, color: isDark ? '#9CA3AF' : '#9CA3AF', fontWeight: '500' },

  // Header
  headerWrapper: {
    backgroundColor: isDark ? '#022C22' : '#064E3B',
    paddingHorizontal: 20,
    paddingBottom: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
    elevation: 0,
    zIndex: 10,
    marginTop: -50,
    paddingTop: 50 + 16,
    marginBottom: 16,
  },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  pageTitle: { fontSize: 26, fontWeight: '800', color: '#FFFFFF', letterSpacing: -0.5 },
  pageSubtitle: { fontSize: 13, color: isDark ? '#34D399' : '#A7F3D0', marginTop: 3, fontWeight: '500' },
  headerStats: { flexDirection: 'row', alignItems: 'center' },
  headerStatBadge: {
    alignItems: 'center', backgroundColor: isDark ? '#F59E0B' : '#FCD34D',
    paddingHorizontal: 10, paddingVertical: 6, borderRadius: 12, marginLeft: 8,
  },
  headerStatNum: { fontSize: 14, fontWeight: '800', color: isDark ? '#FFFBEB' : '#92400E' },
  headerStatLabel: { fontSize: 9, fontWeight: '700', color: isDark ? '#FFFBEB' : '#92400E', marginTop: 1, textTransform: 'uppercase' },

  // Filters
  filterScroll: { maxHeight: 52, flexGrow: 0 },
  filterRow: { paddingHorizontal: 16, paddingBottom: 10, flexDirection: 'row', alignItems: 'center' },
  filterChip: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: 14, paddingVertical: 7,
    borderRadius: 20, backgroundColor: isDark ? '#1F2937' : '#fff',
    borderWidth: 1.5, borderColor: isDark ? '#374151' : '#E5E7EB',
    marginRight: 8,
    ...Platform.select({ android: { elevation: 1 }, ios: { shadowColor: '#000', shadowOpacity: isDark ? 0.3 : 0.05, shadowRadius: 4, shadowOffset: { width: 0, height: 2 } } }),
  },
  filterChipActive: { backgroundColor: isDark ? '#4F46E5' : '#6366F1', borderColor: isDark ? '#4F46E5' : '#6366F1' },
  filterChipText: { fontSize: 13, fontWeight: '600', color: isDark ? '#D1D5DB' : '#6B7280' },
  filterChipTextActive: { color: '#fff' },
  filterBadge: {
    backgroundColor: isDark ? '#374151' : '#F3F4F6', borderRadius: 10,
    paddingHorizontal: 6, paddingVertical: 1, marginLeft: 5,
  },
  filterBadgeActive: { backgroundColor: 'rgba(255,255,255,0.25)' },
  filterBadgeText: { fontSize: 11, fontWeight: '700', color: isDark ? '#D1D5DB' : '#6B7280' },
  filterBadgeTextActive: { color: '#fff' },

  // List
  list: { paddingHorizontal: 16, paddingBottom: 40, paddingTop: 8 },

  // Card
  card: {
    backgroundColor: isDark ? '#1F2937' : '#fff', borderRadius: 20, marginBottom: 12,
    ...Platform.select({
      android: { elevation: 3 },
      ios: { shadowColor: isDark ? '#000' : '#4F46E5', shadowOpacity: isDark ? 0.3 : 0.08, shadowRadius: 12, shadowOffset: { width: 0, height: 4 } },
    }),
  },
  cardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 16, paddingTop: 14, paddingBottom: 10 },
  cardIdBlock: {},
  cardId: { fontSize: 11, fontWeight: '700', color: isDark ? '#6B7280' : '#9CA3AF', letterSpacing: 0.5 },
  cardDate: { fontSize: 11, color: isDark ? '#9CA3AF' : '#D1D5DB', marginTop: 2 },

  statusPill: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 20, borderWidth: 1 },
  statusDot: { width: 6, height: 6, borderRadius: 3, marginRight: 5 },
  statusLabel: { fontSize: 11, fontWeight: '700' },

  doctorRow: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingBottom: 12 },
  doctorAvatar: {
    width: 40, height: 40, borderRadius: 20,
    backgroundColor: isDark ? '#312E81' : '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginRight: 12,
  },
  doctorAvatarText: { fontSize: 16, fontWeight: '800', color: isDark ? '#A5B4FC' : '#6366F1' },
  doctorName: { fontSize: 15, fontWeight: '700', color: isDark ? '#F9FAFB' : '#1E1B4B' },
  doctorPhone: { fontSize: 12, color: isDark ? '#9CA3AF' : '#9CA3AF', marginTop: 2 },
  amountBlock: { alignItems: 'flex-end' },
  cardAmount: { fontSize: 18, fontWeight: '800', color: isDark ? '#F9FAFB' : '#1E1B4B' },
  cardItems: { fontSize: 11, color: isDark ? '#9CA3AF' : '#9CA3AF', marginTop: 2 },

  cardFooter: {
    flexDirection: 'row', alignItems: 'center',
    borderTopWidth: 1, borderTopColor: isDark ? '#374151' : '#F3F4F6',
    paddingHorizontal: 16, paddingVertical: 10,
  },
  cardFooterText: { fontSize: 12, color: isDark ? '#9CA3AF' : '#9CA3AF', marginLeft: 4, marginRight: 4 },
  dot: { width: 3, height: 3, borderRadius: 2, backgroundColor: isDark ? '#4B5563' : '#D1D5DB', marginHorizontal: 4 },
  tapHint: { fontSize: 11, color: isDark ? '#818CF8' : '#A5B4FC', fontWeight: '600' },

  // Empty
  emptyContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 40 },
  emptyIconWrap: {
    width: 80, height: 80, borderRadius: 40,
    backgroundColor: isDark ? '#312E81' : '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginBottom: 20,
  },
  emptyTitle: { fontSize: 18, fontWeight: '700', color: isDark ? '#F3F4F6' : '#374151', marginBottom: 8 },
  emptySubtitle: { fontSize: 14, color: isDark ? '#9CA3AF' : '#9CA3AF', textAlign: 'center', lineHeight: 20 },

  // Modal / Sheet
  overlay: { flex: 1, justifyContent: 'flex-end' },
  overlayBg: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(15, 10, 40, 0.7)' },
  sheet: {
    backgroundColor: isDark ? '#1F2937' : '#fff',
    borderTopLeftRadius: 28, borderTopRightRadius: 28,
    padding: 24, paddingTop: 12,
    maxHeight: '88%',
    ...Platform.select({ android: { elevation: 24 }, ios: { shadowColor: '#000', shadowOpacity: isDark ? 0.4 : 0.2, shadowRadius: 20, shadowOffset: { width: 0, height: -8 } } }),
  },
  sheetHandle: { width: 40, height: 4, borderRadius: 2, backgroundColor: isDark ? '#4B5563' : '#E5E7EB', alignSelf: 'center', marginBottom: 18 },
  sheetHeader: { flexDirection: 'row', alignItems: 'flex-start', marginBottom: 8 },
  sheetTitle: { fontSize: 20, fontWeight: '800', color: isDark ? '#F9FAFB' : '#1E1B4B' },
  sheetSubtitle: { fontSize: 13, color: isDark ? '#9CA3AF' : '#6B7280', marginTop: 2 },
  closeBtn: {
    width: 32, height: 32, borderRadius: 16,
    backgroundColor: isDark ? '#374151' : '#F3F4F6', justifyContent: 'center', alignItems: 'center', marginLeft: 8,
  },
  sheetMeta: { flexDirection: 'row', alignItems: 'center', marginBottom: 18 },
  sheetMetaText: { fontSize: 12, color: isDark ? '#9CA3AF' : '#9CA3AF', marginLeft: 5 },
  sheetSectionLabel: { fontSize: 13, fontWeight: '700', color: isDark ? '#9CA3AF' : '#6B7280', marginBottom: 10, textTransform: 'uppercase', letterSpacing: 0.5 },

  itemsList: { maxHeight: 260, marginBottom: 8 },
  itemRow: {
    flexDirection: 'row', alignItems: 'center',
    paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: isDark ? '#374151' : '#F9FAFB',
  },
  itemIndex: { width: 26, height: 26, borderRadius: 13, backgroundColor: isDark ? '#312E81' : '#EEF2FF', justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  itemIndexText: { fontSize: 11, fontWeight: '800', color: isDark ? '#A5B4FC' : '#6366F1' },
  itemName: { fontSize: 14, fontWeight: '600', color: isDark ? '#F3F4F6' : '#1F2937' },
  itemMeta: { fontSize: 12, color: isDark ? '#9CA3AF' : '#9CA3AF', marginTop: 2 },
  itemTotal: { fontSize: 14, fontWeight: '700', color: isDark ? '#818CF8' : '#6366F1' },

  noItemsText: { color: isDark ? '#9CA3AF' : '#9CA3AF', textAlign: 'center', paddingVertical: 20 },

  sheetTotalRow: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    marginTop: 14, paddingTop: 14, borderTopWidth: 2, borderTopColor: isDark ? '#374151' : '#F3F4F6',
  },
  sheetTotalLabel: { fontSize: 14, fontWeight: '600', color: isDark ? '#9CA3AF' : '#6B7280' },
  sheetTotalAmount: { fontSize: 24, fontWeight: '800', color: isDark ? '#F9FAFB' : '#1E1B4B' },

  sheetNotesRow: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: isDark ? '#4C1D95' : '#F5F3FF', borderRadius: 12, padding: 12, marginTop: 12,
  },
  sheetNotesText: { fontSize: 13, color: isDark ? '#DDD6FE' : '#5B21B6', flex: 1 },
});
