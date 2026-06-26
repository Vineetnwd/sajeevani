import React, { useState, useCallback } from 'react';
import { StyleSheet, View, Text, ScrollView, TouchableOpacity, ActivityIndicator, RefreshControl, StatusBar, Alert, Modal, TextInput, useColorScheme } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons, MaterialCommunityIcons } from '@expo/vector-icons';
import { router, useFocusEffect } from 'expo-router';

const API_BASE = 'https://praanveda.net/web/api/mr.php';

const getStatusColors = (isDark: boolean): Record<string, { bg: string; text: string; dot: string }> => ({
  Pending:   { bg: isDark ? '#78350F' : '#FFFBEB', text: isDark ? '#FDE68A' : '#B45309', dot: '#F59E0B' },
  Confirmed: { bg: isDark ? '#1E3A8A' : '#EFF6FF', text: isDark ? '#BFDBFE' : '#1D4ED8', dot: '#3B82F6' },
  Dispatched:{ bg: '#EEF2FF', text: '#4338CA', dot: '#6366F1' },
  Delivered: { bg: isDark ? '#064E3B' : '#F0FDF4', text: isDark ? '#6EE7B7' : '#15803D', dot: '#22C55E' },
  Cancelled: { bg: isDark ? '#7F1D1D' : '#FEF2F2', text: isDark ? '#FECACA' : '#B91C1C', dot: '#EF4444' },
});

export default function DoctorHomeScreen() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const styles = getStyles(isDark);
  const statusColors = getStatusColors(isDark);

  const [userName, setUserName] = useState('');
  const [userId, setUserId] = useState('');
  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Delivery Modal State
  const [deliveryModalVisible, setDeliveryModalVisible] = useState(false);
  const [deliveringOrderId, setDeliveringOrderId] = useState<string | null>(null);
  const [deliveryRemark, setDeliveryRemark] = useState('');
  const [isDelivering, setIsDelivering] = useState(false);

  const fetchOrders = useCallback(async (uid: string) => {
    if (!uid) { setLoading(false); return; }
    try {
      const res = await fetch(`${API_BASE}?action=get_doctor_orders&doctor_id=${uid}&t=${Date.now()}`);
      const result = await res.json();
      if (result.status === 'success') {
        setOrders(result.data || []);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      AsyncStorage.multiGet(['userName', 'userId']).then((values) => {
        const name = values[0][1] || 'Doctor';
        const id = values[1][1] || '';
        setUserName(name);
        setUserId(id);
        if (id) fetchOrders(id);
      });
    }, [fetchOrders])
  );

  const handleLogout = async () => {
    Alert.alert('Sign Out', 'Are you sure you want to sign out?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Sign Out', style: 'destructive',
        onPress: async () => {
          await AsyncStorage.multiRemove(['userToken', 'userId', 'userRole', 'userName']);
          router.replace('/login');
        }
      }
    ]);
  };

  const confirmDelivery = async () => {
    if (!deliveringOrderId) return;
    setIsDelivering(true);
    try {
      const formData = new FormData();
      formData.append('order_id', deliveringOrderId);
      if (deliveryRemark.trim()) {
        formData.append('remark', deliveryRemark.trim());
      }
      
      const res = await fetch(`${API_BASE}?action=doctor_mark_delivered`, {
        method: 'POST',
        body: formData,
      });
      const result = await res.json();
      
      if (result.status === 'success') {
        Alert.alert('Success', 'Order marked as Delivered!');
        setDeliveryModalVisible(false);
        setDeliveryRemark('');
        fetchOrders(userId);
      } else {
        Alert.alert('Error', result.message || 'Failed to update order');
      }
    } catch (e) {
      Alert.alert('Error', 'Network error. Try again.');
    } finally {
      setIsDelivering(false);
      setDeliveringOrderId(null);
    }
  };

  const markDelivered = (orderId: string) => {
    setDeliveringOrderId(orderId);
    setDeliveryRemark('');
    setDeliveryModalVisible(true);
  };

  const totalOrders   = orders.length;
  const pendingOrders = orders.filter(o => o.status === 'Pending').length;
  const deliveredOrders = orders.filter(o => o.status === 'Delivered').length;

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#10B981" />
      </View>
    );
  }

  return (
    <View style={{ flex: 1, backgroundColor: isDark ? '#022C22' : '#064E3B' }}>
      <SafeAreaView style={{ flex: 1, backgroundColor: isDark ? '#111827' : '#F0FDF4' }} edges={['top']}>
        <StatusBar barStyle="light-content" backgroundColor={isDark ? '#022C22' : '#064E3B'} />
        
        {/* Header - We use absolute positioning to extend it to the top behind the safe area, or just rely on a standard layout. Actually the safest is: */}

        <View style={styles.headerWrapper}>
          <View style={styles.header}>
            <View>
              <Text style={styles.greeting}>Welcome back,</Text>
              <Text style={styles.userName}>Dr. {userName.replace(/^Dr\.\s*/i, '')}</Text>
              <View style={styles.roleBadge}>
                <Text style={styles.roleBadgeText}>Clinic Dashboard</Text>
              </View>
            </View>
            <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
              <Ionicons name="log-out-outline" size={20} color="#064E3B" />
            </TouchableOpacity>
          </View>
        </View>

        <ScrollView
          contentContainerStyle={styles.scroll}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchOrders(userId); }} colors={['#10B981']} />}
        >
          {/* Stats Grid */}
          <View style={styles.statsGrid}>
          <View style={styles.statCard}>
            <MaterialCommunityIcons name="clipboard-list-outline" size={24} color="#059669" />
            <Text style={styles.statNum}>{totalOrders}</Text>
            <Text style={styles.statLabel}>Total</Text>
          </View>
          <View style={styles.statCard}>
            <MaterialCommunityIcons name="clock-outline" size={24} color="#D97706" />
            <Text style={[styles.statNum, { color: '#D97706' }]}>{pendingOrders}</Text>
            <Text style={styles.statLabel}>Pending</Text>
          </View>
          <View style={styles.statCard}>
            <MaterialCommunityIcons name="check-decagram-outline" size={24} color="#059669" />
            <Text style={[styles.statNum, { color: '#059669' }]}>{deliveredOrders}</Text>
            <Text style={styles.statLabel}>Delivered</Text>
          </View>
        </View>

        {/* Orders List */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>My Orders</Text>
        </View>

        {orders.length === 0 ? (
          <View style={styles.emptyBox}>
            <Ionicons name="document-text-outline" size={48} color="#D1D5DB" />
            <Text style={styles.emptyText}>No orders found</Text>
            <Text style={styles.emptySubtext}>Orders placed for your clinic will appear here.</Text>
          </View>
        ) : (
          orders.map((order) => {
            const sc = statusColors[order.status] || statusColors['Pending'];
            return (
              <View key={order.id} style={styles.orderCard}>
                <View style={styles.orderCardTop}>
                  <View>
                    <Text style={styles.orderIdText}>#DO-{order.id}</Text>
                    <Text style={styles.doctorName}>MR: {order.mr_name}</Text>
                  </View>
                  <View style={{ alignItems: 'flex-end' }}>
                    <View style={[styles.statusBadge, { backgroundColor: sc.bg }]}>
                      <View style={[styles.statusDot, { backgroundColor: sc.dot }]} />
                      <Text style={[styles.statusText, { color: sc.text }]}>{order.status}</Text>
                    </View>
                    {order.payment_status === 'Paid' && (
                      <View style={[styles.statusBadge, { backgroundColor: isDark ? '#065F46' : '#D1FAE5', marginTop: 4, paddingVertical: 2 }]}>
                        <Ionicons name="checkmark-circle" size={10} color={isDark ? '#6EE7B7' : '#059669'} style={{marginRight: 4}} />
                        <Text style={[styles.statusText, { color: isDark ? '#6EE7B7' : '#047857', fontSize: 9 }]}>PAID</Text>
                      </View>
                    )}
                  </View>
                </View>
                
                {order.status_remarks && (
                  <View style={[styles.notesRow, { backgroundColor: isDark ? '#064E3B' : '#F0FDF4', borderLeftColor: isDark ? '#10B981' : '#059669' }]}>
                    <Text style={styles.notesText}><Text style={{fontWeight:'700', color: isDark ? '#6EE7B7' : '#064E3B'}}>Remark: </Text>{order.status_remarks}</Text>
                  </View>
                )}
                {order.status === 'Dispatched' && (order.courier_company || order.awb_no) && (
                  <View style={[styles.notesRow, { backgroundColor: isDark ? '#064E3B' : '#F0FDF4', borderLeftColor: isDark ? '#10B981' : '#059669', flexDirection: 'column', alignItems: 'flex-start' }]}>
                     {order.courier_company && <Text style={styles.notesText}><Text style={{fontWeight:'700', color: isDark ? '#6EE7B7' : '#064E3B'}}>Courier: </Text>{order.courier_company}</Text>}
                     {order.awb_no && <Text style={[styles.notesText, { marginTop: 2 }]}><Text style={{fontWeight:'700', color: isDark ? '#6EE7B7' : '#064E3B'}}>AWB: </Text>{order.awb_no}</Text>}
                  </View>
                )}

                <View style={styles.orderCardBottom}>
                  <View style={{ flex: 1, flexDirection: 'row', alignItems: 'center' }}>
                    <Text style={styles.orderMeta}>{order.item_count} item{order.item_count !== 1 ? 's' : ''}</Text>
                    <Text style={styles.orderMeta}>•</Text>
                    <Text style={styles.orderMeta}>{new Date(order.created_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })}</Text>
                  </View>
                  <Text style={styles.orderAmount}>₹{parseFloat(order.total_amount).toFixed(2)}</Text>
                </View>

                {order.status === 'Dispatched' && (
                  <TouchableOpacity 
                    style={styles.markDeliveredBtn} 
                    onPress={() => markDelivered(order.id.toString())}
                  >
                    <Ionicons name="checkmark-done-outline" size={18} color="#fff" />
                    <Text style={styles.markDeliveredBtnText}>Mark as Delivered</Text>
                  </TouchableOpacity>
                )}

                {order.payment_status === 'Paid' && (
                  <TouchableOpacity 
                    style={[styles.markDeliveredBtn, { backgroundColor: isDark ? '#374151' : '#F8FAFC', borderWidth: 1, borderColor: isDark ? '#4B5563' : '#E2E8F0', elevation: 0, marginTop: order.status === 'Dispatched' ? 8 : 12 }]} 
                    onPress={() => router.push({ pathname: '/(doctor)/receipt', params: { orderId: order.id } })}
                  >
                    <Ionicons name="receipt-outline" size={18} color={isDark ? '#F9FAFB' : '#0F172A'} />
                    <Text style={[styles.markDeliveredBtnText, { color: isDark ? '#F9FAFB' : '#0F172A' }]}>View Payment Receipt</Text>
                  </TouchableOpacity>
                )}
              </View>
            );
          })
        )}
        </ScrollView>

        {/* Delivery Modal */}
        {deliveryModalVisible && (
          <Modal transparent visible={deliveryModalVisible} animationType="fade">
            <View style={styles.modalOverlay}>
              <View style={styles.modalContent}>
                <Text style={styles.modalTitle}>Confirm Delivery</Text>
                <Text style={styles.modalSubtitle}>Have you received this order in good condition?</Text>
                
                <Text style={styles.modalLabel}>Add a Remark (Optional)</Text>
                <TextInput
                  style={styles.modalInput}
                  placeholder="e.g., Package arrived safely, all items present."
                  placeholderTextColor={isDark ? '#6B7280' : '#D1D5DB'}
                  value={deliveryRemark}
                  onChangeText={setDeliveryRemark}
                  multiline
                  numberOfLines={3}
                  textAlignVertical="top"
                />

                <View style={styles.modalActions}>
                  <TouchableOpacity 
                    style={styles.modalCancelBtn} 
                    onPress={() => {
                      setDeliveryModalVisible(false);
                      setDeliveringOrderId(null);
                      setDeliveryRemark('');
                    }}
                    disabled={isDelivering}
                  >
                    <Text style={styles.modalCancelText}>Cancel</Text>
                  </TouchableOpacity>
                  <TouchableOpacity 
                    style={styles.modalConfirmBtn} 
                    onPress={confirmDelivery}
                    disabled={isDelivering}
                  >
                    {isDelivering ? (
                      <ActivityIndicator color="#fff" size="small" />
                    ) : (
                      <Text style={styles.modalConfirmText}>Mark Delivered</Text>
                    )}
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          </Modal>
        )}

      </SafeAreaView>
    </View>
  );
}

const getStyles = (isDark: boolean) => StyleSheet.create({
  container: { flex: 1, backgroundColor: isDark ? '#111827' : '#F0FDF4' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: isDark ? '#111827' : '#F0FDF4' },
  scroll: { paddingBottom: 20 },

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
  },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  greeting: { fontSize: 13, color: isDark ? '#34D399' : '#A7F3D0', fontWeight: '500' },
  userName: { fontSize: 22, fontWeight: '800', color: '#FFFFFF', letterSpacing: -0.5, marginTop: 2 },
  roleBadge: { backgroundColor: 'rgba(255,255,255,0.2)', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6, alignSelf: 'flex-start', marginTop: 6 },
  roleBadgeText: { fontSize: 10, fontWeight: '700', color: '#ECFDF5', textTransform: 'uppercase' },
  logoutBtn: { width: 36, height: 36, borderRadius: 18, backgroundColor: isDark ? '#065F46' : '#D1FAE5', justifyContent: 'center', alignItems: 'center' },

  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', marginTop: 20, paddingHorizontal: 16, marginBottom: 20 },
  statCard: { flex: 1, minWidth: '30%', backgroundColor: isDark ? '#1F2937' : '#FFFFFF', borderRadius: 16, paddingVertical: 12, paddingHorizontal: 8, alignItems: 'center', margin: 4, elevation: 3, shadowColor: '#000', shadowOpacity: isDark ? 0.3 : 0.05, shadowRadius: 5, shadowOffset: { width: 0, height: 2 } },
  statNum: { fontSize: 22, fontWeight: '800', color: isDark ? '#10B981' : '#064E3B', marginVertical: 4 },
  statLabel: { fontSize: 10, fontWeight: '700', color: isDark ? '#9CA3AF' : '#64748B', textTransform: 'uppercase' },

  sectionHeader: { paddingHorizontal: 20, marginBottom: 12 },
  sectionTitle: { fontSize: 16, fontWeight: '800', color: isDark ? '#10B981' : '#064E3B' },

  orderCard: { backgroundColor: isDark ? '#1F2937' : '#FFFFFF', borderRadius: 16, padding: 16, marginHorizontal: 16, marginBottom: 12, elevation: 2, shadowColor: '#000', shadowOpacity: isDark ? 0.3 : 0.05, shadowRadius: 5, shadowOffset: { width: 0, height: 2 } },
  orderCardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  orderIdText: { fontSize: 11, fontWeight: '700', color: isDark ? '#6B7280' : '#94A3B8' },
  doctorName: { fontSize: 14, fontWeight: '700', color: isDark ? '#F9FAFB' : '#0F172A', marginTop: 2 },
  statusBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8 },
  statusDot: { width: 6, height: 6, borderRadius: 3, marginRight: 6 },
  statusText: { fontSize: 11, fontWeight: '700' },
  
  notesRow: { padding: 10, borderRadius: 8, marginBottom: 8, borderLeftWidth: 3 },
  notesText: { fontSize: 12, color: isDark ? '#D1D5DB' : '#334155' },

  orderCardBottom: { flexDirection: 'row', alignItems: 'center', marginTop: 6, paddingTop: 10, borderTopWidth: 1, borderTopColor: isDark ? '#374151' : '#F1F5F9' },
  orderMeta: { fontSize: 11, color: isDark ? '#9CA3AF' : '#64748B', marginRight: 6, fontWeight: '500' },
  orderAmount: { fontSize: 15, fontWeight: '800', color: isDark ? '#10B981' : '#059669', textAlign: 'right' },

  markDeliveredBtn: {
    backgroundColor: isDark ? '#10B981' : '#10B981',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 12,
    borderRadius: 12,
    marginTop: 12,
    shadowColor: isDark ? '#10B981' : '#10B981',
    shadowOpacity: 0.3,
    shadowRadius: 6,
    shadowOffset: { width: 0, height: 3 },
    elevation: 3,
  },
  markDeliveredBtnText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '700',
    marginLeft: 6,
  },

  emptyBox: { alignItems: 'center', paddingVertical: 40 },
  emptyText: { fontSize: 16, fontWeight: '700', color: isDark ? '#D1D5DB' : '#475569', marginTop: 12 },
  emptySubtext: { fontSize: 12, color: isDark ? '#6B7280' : '#94A3B8', marginTop: 4 },

  modalOverlay: { position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', alignItems: 'center', zIndex: 100 },
  modalContent: { backgroundColor: isDark ? '#1F2937' : '#fff', width: '85%', borderRadius: 16, padding: 24, shadowColor: '#000', shadowOpacity: isDark ? 0.5 : 0.1, shadowRadius: 10, elevation: 10 },
  modalTitle: { fontSize: 18, fontWeight: '800', color: isDark ? '#10B981' : '#064E3B', marginBottom: 6 },
  modalSubtitle: { fontSize: 13, color: isDark ? '#9CA3AF' : '#475569', marginBottom: 20 },
  modalLabel: { fontSize: 13, fontWeight: '700', color: isDark ? '#F9FAFB' : '#0F172A', marginBottom: 8 },
  modalInput: { backgroundColor: isDark ? '#374151' : '#F8FAFC', borderRadius: 10, padding: 12, fontSize: 14, color: isDark ? '#F9FAFB' : '#1E293B', borderWidth: 1, borderColor: isDark ? '#4B5563' : '#E2E8F0', minHeight: 80, marginBottom: 20 },
  modalActions: { flexDirection: 'row', justifyContent: 'flex-end', marginTop: 8 },
  modalCancelBtn: { paddingHorizontal: 16, paddingVertical: 12, marginRight: 8, borderRadius: 10 },
  modalCancelText: { color: isDark ? '#9CA3AF' : '#64748B', fontSize: 14, fontWeight: '700' },
  modalConfirmBtn: { backgroundColor: isDark ? '#10B981' : '#10B981', paddingHorizontal: 20, paddingVertical: 12, borderRadius: 10, minWidth: 100, alignItems: 'center' },
  modalConfirmText: { color: '#fff', fontSize: 14, fontWeight: '700' },
});
